<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BonusCollection;
use App\Models\KdCustomer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BonusCollectionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeBonusAccess();

        $filter = $request->query('filter', 'all'); // all, disbursed, undisbursed

        $collections = BonusCollection::with(['user', 'disbursedBy'])
            ->when($filter === 'disbursed', fn ($q) => $q->where('is_disbursed', true))
            ->when($filter === 'undisbursed', fn ($q) => $q->where('is_disbursed', false))
            ->when($request->query('code'), fn ($q, $code) => $q->where('code', 'like', '%' . $code . '%'))
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.bonus.index', [
            'collections' => $collections,
            'codeFilter' => $request->query('code'),
            'filter' => $filter,
        ]);
    }

    public function create()
    {
        $this->authorizeBonusUpload();

        return view('admin.bonus.create');
    }

    public function store(Request $request)
    {
        $this->authorizeBonusUpload();

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['file' => 'Could not read the file. Error: ' . $e->getMessage()]);
        }

        $headerRow = null;
        $headerCols = [];
        $codeAliases = ['CODE', 'KDNO', 'CUSTOMERNO', 'KD_NUMBER', 'KDNO', 'CUSTOMER'];
        $normalize = fn ($s) => strtoupper(preg_replace('/[\s_\-\.]+/', '', (string) $s));
        foreach (array_slice($rows, 0, 15) as $idx => $row) {
            $row = array_map(fn ($c) => trim(preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $c)), $row);
            $rowNormalized = array_map($normalize, $row);
            $hasCode = ! empty(array_intersect($codeAliases, $rowNormalized));
            if (! $hasCode) {
                foreach ($row as $cell) {
                    if (stripos((string) $cell, 'code') !== false || stripos((string) $cell, 'kdno') !== false || stripos((string) $cell, 'kd no') !== false) {
                        $hasCode = true;
                        break;
                    }
                }
            }
            if ($hasCode) {
                $headerRow = $idx;
                foreach ($row as $col => $val) {
                    $v = trim((string) $val);
                    $vUpper = strtoupper($v);
                    $vUpperNoSpace = str_replace([' ', '_'], '', $vUpper);
                    if (($vUpper === 'NO' || $vUpper === 'NO.') && ! isset($headerCols['No'])) {
                        $headerCols['No'] = $col;
                    } elseif ($vUpper !== 'NO' && (in_array($vUpper, ['CODE', 'KD NO', 'KDNO']) || in_array($vUpperNoSpace, ['CODE', 'KDNO', 'CUSTOMERNO']) || stripos($v, 'code') !== false || stripos($v, 'kdno') !== false || stripos($v, 'kd no') !== false)) {
                        $headerCols['Code'] = $col;
                    } elseif ($vUpper === 'NAME') {
                        $headerCols['Name'] = $col;
                    } elseif ($vUpper === 'DATE') {
                        $headerCols['Date'] = $col;
                    } elseif ($vUpper === 'SC') {
                        $headerCols['SC'] = $col;
                    } elseif ($vUpper === 'GRADE') {
                        $headerCols['Grade'] = $col;
                    } elseif ($vUpper === 'HONORARY') {
                        $headerCols['Honorary'] = $col;
                    } elseif ($vUpper === 'TOTAL') {
                        $headerCols['Total'] = $col;
                    }
                }
                break;
            }
        }

        if ($headerRow === null) {
            return back()->withInput()->withErrors(['file' => 'Could not find a header row with "Code" (KD NO). Ensure your Excel has a row with column headers including Code.']);
        }
        if (! isset($headerCols['Code'])) {
            $headerCols['Code'] = $headerCols['code'] ?? null;
            foreach ($headerCols as $k => $colIndex) {
                if (strtoupper(trim((string) $k)) === 'CODE') {
                    $headerCols['Code'] = $colIndex;
                    break;
                }
            }
        }
        if (! isset($headerCols['Code']) || $headerCols['Code'] === null) {
            $headerRowData = $rows[$headerRow] ?? [];
            $headerRowData = array_map(fn ($c) => trim(preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $c)), $headerRowData);
            foreach ($headerRowData as $col => $cell) {
                if (stripos((string) $cell, 'code') !== false || stripos((string) $cell, 'kdno') !== false || stripos((string) $cell, 'kd no') !== false) {
                    $headerCols['Code'] = $col;
                    break;
                }
            }
        }
        if (! isset($headerCols['Code']) || $headerCols['Code'] === null) {
            $found = implode(', ', array_keys(array_filter($headerCols, fn ($v) => $v !== null)));
            return back()->withInput()->withErrors(['file' => 'Could not find "Code" (KD NO) column. Found columns: ' . $found . '.']);
        }

        $dataRows = array_slice($rows, $headerRow + 1);
        $kdCustomers = KdCustomer::whereNotNull('user_id')->pluck('user_id', 'kd_no')->all();

        $created = 0;
        $matched = 0;

        DB::beginTransaction();
        try {
            foreach ($dataRows as $row) {
                $code = isset($headerCols['Code']) ? trim((string) ($row[$headerCols['Code']] ?? '')) : '';
                $name = isset($headerCols['Name']) ? trim((string) ($row[$headerCols['Name']] ?? '')) : '';
                $dateVal = isset($headerCols['Date']) ? trim((string) ($row[$headerCols['Date']] ?? '')) : '';
                $sc = isset($headerCols['SC']) ? (int) ($row[$headerCols['SC']] ?? 0) : 0;
                $grade = isset($headerCols['Grade']) ? (int) ($row[$headerCols['Grade']] ?? 0) : null;
                $honorary = isset($headerCols['Honorary']) ? trim((string) ($row[$headerCols['Honorary']] ?? '')) : null;
                $total = isset($headerCols['Total']) ? (float) preg_replace('/[^0-9.-]/', '', (string) ($row[$headerCols['Total']] ?? 0)) : 0;
                $no = isset($headerCols['No']) ? (int) ($row[$headerCols['No']] ?? 0) : null;

                if (! $code && ! $name) {
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

                $userId = $this->resolveUserIdForCode($code, $kdCustomers);
                if ($userId) {
                    $matched++;
                }

                BonusCollection::create([
                    'no' => $no ?: null,
                    'code' => $code ?: '—',
                    'name' => $name ?: '—',
                    'record_date' => $recordDate,
                    'sc' => $sc,
                    'grade' => $grade ?: null,
                    'honorary' => $honorary ?: null,
                    'total' => $total,
                    'user_id' => $userId,
                ]);
                $created++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['file' => 'Error importing: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.bonus.index')
            ->with('success', "Imported {$created} bonus records. " . ($matched > 0 ? "Matched {$matched} to user accounts." : ''));
    }

    public function rematch()
    {
        $this->authorizeBonusUpload();

        $kdCustomers = KdCustomer::whereNotNull('user_id')->pluck('user_id', 'kd_no')->all();
        $unmatched = BonusCollection::whereNull('user_id')->where('code', '!=', '—')->get();
        $matched = 0;

        foreach ($unmatched as $c) {
            $userId = $this->resolveUserIdForCode(trim($c->code), $kdCustomers);
            if ($userId) {
                $c->update(['user_id' => $userId]);
                $matched++;
            }
        }

        return redirect()->route('admin.bonus.index')
            ->with('success', "Re-matched {$matched} of {$unmatched->count()} unmatched records.");
    }

    private function resolveUserIdForCode(string $code, array $kdCustomers): ?int
    {
        $code = trim($code);
        if (! $code) {
            return null;
        }
        $userId = $kdCustomers[$code] ?? $kdCustomers[strtoupper($code)] ?? $kdCustomers[strtolower($code)] ?? null;
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

    public function toggleDisbursement(Request $request, BonusCollection $bonus)
    {
        $this->authorizeBonusAccess();

        $bonus->update([
            'is_disbursed' => !$bonus->is_disbursed,
            'disbursed_at' => !$bonus->is_disbursed ? now() : null,
            'disbursed_by_user_id' => !$bonus->is_disbursed ? auth()->id() : null,
        ]);

        $status = $bonus->is_disbursed ? 'disbursed' : 'undisbursed';
        return redirect()->route('admin.bonus.index', ['filter' => $request->query('filter', 'all')])
            ->with('success', "Bonus record #{$bonus->id} ({$bonus->code}) marked as {$status}.");
    }

    public function bulkDisburse(Request $request)
    {
        $this->authorizeBonusAccess();

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:bonus_collections,id',
        ]);

        $count = BonusCollection::whereIn('id', $request->ids)
            ->where('is_disbursed', false)
            ->update([
                'is_disbursed' => true,
                'disbursed_at' => now(),
                'disbursed_by_user_id' => auth()->id(),
            ]);

        return redirect()->route('admin.bonus.index', ['filter' => $request->query('filter', 'all')])
            ->with('success', "Marked {$count} bonus record(s) as disbursed.");
    }

    public function bulkUndisburse(Request $request)
    {
        $this->authorizeBonusAccess();

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:bonus_collections,id',
        ]);

        $count = BonusCollection::whereIn('id', $request->ids)
            ->where('is_disbursed', true)
            ->update([
                'is_disbursed' => false,
                'disbursed_at' => null,
                'disbursed_by_user_id' => null,
            ]);

        return redirect()->route('admin.bonus.index', ['filter' => $request->query('filter', 'all')])
            ->with('success', "Marked {$count} bonus record(s) as undisbursed.");
    }

    private function authorizeBonusUpload(): void
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && $user->role?->name !== 'headquarters') {
            abort(403, 'Only Headquarters or Super Admin can upload bonus lists.');
        }
    }

    private function authorizeBonusAccess(): void
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && $user->role?->name !== 'headquarters' && $user->role?->name !== 'accountant') {
            abort(403, 'Only Super Admin, Headquarters, or Accountant can access bonus management.');
        }
    }
}
