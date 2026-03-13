<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DpbvCollection;
use App\Models\KdCustomer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DpbvCollectionController extends Controller
{
    /**
     * HQ-only: Show upload form and list recent collections.
     */
    public function index(Request $request)
    {
        $this->authorizeDpbvUpload();

        $collections = DpbvCollection::with('user')
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->when($request->query('code'), fn ($q, $code) => $q->where('code', 'like', '%' . $code . '%'))
            ->paginate(50)
            ->withQueryString();

        return view('admin.dpbv.index', [
            'collections' => $collections,
            'codeFilter' => $request->query('code'),
        ]);
    }

    /**
     * HQ-only: Show upload form.
     */
    public function create()
    {
        $this->authorizeDpbvUpload();

        return view('admin.dpbv.create');
    }

    /**
     * HQ-only: Process Excel upload, match CODE to users via kd_customers, and store.
     */
    public function store(Request $request)
    {
        $this->authorizeDpbvUpload();

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['file' => 'Could not read the file. Ensure it is a valid Excel file (.xlsx, .xls) or CSV. Error: ' . $e->getMessage()]);
        }

        $created = 0;
        $matched = 0;
        $skipped = 0;
        $errors = [];

        // Find header row (row with NO, CODE, NAME, DATE, SC, DPBV)
        $headerRow = null;
        $headerCols = [];
        foreach ($rows as $idx => $row) {
            $row = array_map(fn ($c) => is_string($c) ? trim($c) : $c, $row);
            if (in_array('CODE', $row) || in_array('NO', $row) || in_array('DPBV', $row)) {
                $headerRow = $idx;
                foreach ($row as $col => $val) {
                    $val = (string) $val;
                    if (in_array($val, ['NO', 'CODE', 'NAME', 'DATE', 'SC', 'DPBV'])) {
                        $headerCols[$val] = $col;
                    }
                }
                break;
            }
        }

        if (! $headerRow || empty($headerCols)) {
            return back()->withInput()->withErrors(['file' => 'Could not find required columns (NO, CODE, NAME, DATE, SC, DPBV). Please use the standard DPBV COLLECTION format.']);
        }

        $dataRows = array_slice($rows, $headerRow + 1);
        $kdCustomers = KdCustomer::whereNotNull('user_id')->pluck('user_id', 'kd_no')->all();

        DB::beginTransaction();
        try {
            foreach ($dataRows as $row) {
                $code = isset($headerCols['CODE']) ? trim((string) ($row[$headerCols['CODE']] ?? '')) : '';
                $name = isset($headerCols['NAME']) ? trim((string) ($row[$headerCols['NAME']] ?? '')) : '';
                $dateVal = isset($headerCols['DATE']) ? trim((string) ($row[$headerCols['DATE']] ?? '')) : '';
                $sc = isset($headerCols['SC']) ? trim((string) ($row[$headerCols['SC']] ?? '')) : '';
                $dpbv = isset($headerCols['DPBV']) ? (float) ($row[$headerCols['DPBV']] ?? 0) : 0;
                $no = isset($headerCols['NO']) ? (int) ($row[$headerCols['NO']] ?? 0) : null;

                if (! $code && ! $name) {
                    $skipped++;
                    continue;
                }

                $recordDate = null;
                if ($dateVal !== '') {
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $dateVal)) {
                        $recordDate = (string) $dateVal;
                    } elseif (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}$/', (string) $dateVal) || preg_match('/^\d{1,2}-\d{1,2}-\d{2,4}$/', (string) $dateVal)) {
                        $ts = strtotime((string) $dateVal);
                        $recordDate = $ts ? date('Y-m-d', $ts) : null;
                    } elseif (is_numeric($dateVal)) {
                        try {
                            $recordDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $dateVal)->format('Y-m-d');
                        } catch (\Throwable) {
                            $recordDate = date('Y-m-d');
                        }
                    }
                }
                if (! $recordDate) {
                    $recordDate = date('Y-m-d');
                }

                $userId = $this->resolveUserIdForCode($code, $sc, $kdCustomers);
                if ($userId) {
                    $matched++;
                }

                DpbvCollection::create([
                    'no' => $no ?: null,
                    'code' => $code ?: '—',
                    'name' => $name ?: '—',
                    'record_date' => $recordDate,
                    'sc' => $sc,
                    'dpbv' => $dpbv,
                    'user_id' => $userId,
                ]);
                $created++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['file' => 'Error importing: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.dpbv.index')
            ->with('success', "Imported {$created} records. " . ($matched > 0 ? "Matched {$matched} to user accounts." : '') . ($skipped > 0 ? " Skipped {$skipped} empty rows." : ''));
    }

    /**
     * Re-match unmatched DPBV records against kd_customers and orders.
     * Use when orders were placed after the initial upload.
     */
    public function rematch()
    {
        $this->authorizeDpbvUpload();

        $kdCustomers = KdCustomer::whereNotNull('user_id')->pluck('user_id', 'kd_no')->all();
        $unmatched = DpbvCollection::whereNull('user_id')->where('code', '!=', '—')->get();
        $matched = 0;

        foreach ($unmatched as $c) {
            $code = trim($c->code);
            if (! $code) {
                continue;
            }
            $sc = trim((string) $c->sc);
            $userId = $this->resolveUserIdForCode($code, $sc, $kdCustomers);
            if ($userId) {
                $c->update(['user_id' => $userId]);
                $matched++;
            }
        }

        return redirect()->route('admin.dpbv.index')
            ->with('success', "Re-matched {$matched} of {$unmatched->count()} unmatched records.");
    }

    /**
     * Resolve user_id for a CODE (KD NO): check kd_customers first, then orders.kd_id from shopping.
     */
    private function resolveUserIdForCode(string $code, string $sc, array $kdCustomers): ?int
    {
        $code = trim($code);
        $sc = trim($sc);

        // 1. Match from service_center_code if SC is provided
        if ($sc !== '') {
            $scUser = \App\Models\User::where('service_center_code', $sc)
                ->orWhere('service_center_code', strtoupper($sc))
                ->orWhere('service_center_code', strtolower($sc))
                ->first();
            if ($scUser) {
                return (int) $scUser->id;
            }
        }

        if (! $code) {
            return null;
        }

        // 2. Match from kd_customers (KD NO registry)
        $userId = $kdCustomers[$code] ?? $kdCustomers[strtoupper($code)] ?? $kdCustomers[strtolower($code)] ?? null;
        
        // 3. If no match, check orders.kd_id (KD NO from shopping)
        if (! $userId) {
            $orderUser = Order::whereNotNull('user_id')
                ->where(function ($q) use ($code) {
                    $q->where('kd_id', $code)
                        ->orWhere('kd_id', strtoupper($code))
                        ->orWhere('kd_id', strtolower($code));
                })
                ->orderByDesc('id')
                ->first();
            $userId = $orderUser?->user_id;
        }

        return $userId ? (int) $userId : null;
    }

    private function authorizeDpbvUpload(): void
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && $user->role?->name !== 'headquarters') {
            abort(403, 'Only Headquarters or Super Admin can upload DPBV collections.');
        }
    }
}
