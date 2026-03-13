<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KdCustomer;
use App\Models\Order;
use App\Models\PromoCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PromoCollectionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePromoUpload();

        $collections = PromoCollection::with('user')
            ->orderByDesc('id')
            ->when($request->query('code'), fn ($q, $code) => $q->where('customer_no', 'like', '%' . $code . '%'))
            ->when($request->query('promo'), fn ($q, $p) => $q->where('promo_name', 'like', '%' . $p . '%'))
            ->paginate(50)
            ->withQueryString();

        return view('admin.promo.index', [
            'collections' => $collections,
            'codeFilter' => $request->query('code'),
            'promoFilter' => $request->query('promo'),
        ]);
    }

    public function create()
    {
        $this->authorizePromoUpload();

        return view('admin.promo.create');
    }

    public function store(Request $request)
    {
        $this->authorizePromoUpload();

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'promo_name' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $promoNameOverride = trim((string) $request->input('promo_name', ''));

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['file' => 'Could not read the file. Error: ' . $e->getMessage()]);
        }

        // Find header row (ShopNO, CustomerNO, CustomerName)
        $headerRow = null;
        $headerCols = [];
        $promoItemCol = null;
        $promoItemHeader = null;
        $promoMetaCol = null;
        $promoNameFromSheet = null;

        foreach ($rows as $idx => $row) {
            $row = array_map(fn ($c) => is_string($c) ? trim((string) $c) : $c, $row);
            $rowStr = array_map('strval', $row);

            // Check for CustomerNO (or CustomerNo)
            if (in_array('CustomerNO', $rowStr) || in_array('CustomerNo', $rowStr) || in_array('CUSTOMERNO', array_map('strtoupper', $rowStr))) {
                $headerRow = $idx;
                foreach ($row as $col => $val) {
                    $v = (string) $val;
                    $vUpper = strtoupper($v);
                    if ($vUpper === 'SHOPNO' || $v === 'ShopNO') {
                        $headerCols['ShopNO'] = $col;
                    } elseif ($vUpper === 'CUSTOMERNO' || $v === 'CustomerNO' || $v === 'CustomerNo') {
                        $headerCols['CustomerNO'] = $col;
                    } elseif ($vUpper === 'CUSTOMERNAME' || $v === 'CustomerName') {
                        $headerCols['CustomerName'] = $col;
                    }
                }
                // Promo item = first column after CustomerName (header = item name, value = quantity)
                $customerNameCol = $headerCols['CustomerName'] ?? 1;
                for ($c = $customerNameCol + 1; $c < count($row); $c++) {
                    $h = trim((string) ($row[$c] ?? ''));
                    if ($h !== '' && ! in_array(strtoupper($h), ['DATE', 'META', 'ID', 'CUSTOMERNO', 'CUSTOMERNAME'])) {
                        $promoItemCol = $c;
                        $promoItemHeader = $h;
                        break;
                    }
                }
                if ($promoItemCol !== null) {
                    $promoMetaCol = $promoItemCol + 1;
                }
                break;
            }
        }

        // Try to get promo name from row 1 (merged cells often in C1:E1)
        if (! $promoNameOverride && isset($rows[0])) {
            $firstRow = $rows[0];
            foreach ($firstRow as $cell) {
                $val = trim((string) $cell);
                if (str_contains(strtoupper($val), 'PROMO') || str_contains(strtoupper($val), 'WINNER')) {
                    $promoNameFromSheet = $val;
                    break;
                }
            }
        }

        $promoName = $promoNameOverride ?: $promoNameFromSheet;

        if (! $headerRow || ! isset($headerCols['CustomerNO'])) {
            return back()->withInput()->withErrors(['file' => 'Could not find required columns (ShopNO, CustomerNO, CustomerName). Ensure your Excel has a header row with CustomerNO.']);
        }

        $dataRows = array_slice($rows, $headerRow + 1);
        $kdCustomers = KdCustomer::whereNotNull('user_id')->pluck('user_id', 'kd_no')->all();
        $promoItemName = $promoItemHeader ?? null;

        $created = 0;
        $matched = 0;

        DB::beginTransaction();
        try {
            foreach ($dataRows as $row) {
                $customerNo = isset($headerCols['CustomerNO']) ? trim((string) ($row[$headerCols['CustomerNO']] ?? '')) : '';
                $customerName = isset($headerCols['CustomerName']) ? trim((string) ($row[$headerCols['CustomerName']] ?? '')) : '';
                $shopNo = isset($headerCols['ShopNO']) ? trim((string) ($row[$headerCols['ShopNO']] ?? '')) : null;
                $quantity = $promoItemCol !== null ? (int) ($row[$promoItemCol] ?? 1) : 1;
                $promoMeta = $promoMetaCol !== null ? trim((string) ($row[$promoMetaCol] ?? '')) : null;

                if ($quantity < 1) {
                    $quantity = 1;
                }

                if (! $customerNo && ! $customerName) {
                    continue;
                }

                $userId = $this->resolveUserIdForCode($customerNo, $kdCustomers);

                if ($userId) {
                    $matched++;
                }

                PromoCollection::create([
                    'promo_name' => $promoName ?: null,
                    'shop_no' => $shopNo ?: null,
                    'customer_no' => $customerNo ?: '—',
                    'customer_name' => $customerName ?: '—',
                    'promo_item' => $promoItemName ?: null,
                    'quantity' => $quantity,
                    'promo_meta' => $promoMeta ?: null,
                    'user_id' => $userId,
                ]);
                $created++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['file' => 'Error importing: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.promo.index')
            ->with('success', "Imported {$created} promo records. " . ($matched > 0 ? "Matched {$matched} to user accounts." : ''));
    }

    public function rematch()
    {
        $this->authorizePromoUpload();

        $kdCustomers = KdCustomer::whereNotNull('user_id')->pluck('user_id', 'kd_no')->all();
        $unmatched = PromoCollection::whereNull('user_id')->where('customer_no', '!=', '—')->get();
        $matched = 0;

        foreach ($unmatched as $c) {
            $userId = $this->resolveUserIdForCode(trim($c->customer_no), $kdCustomers);
            if ($userId) {
                $c->update(['user_id' => $userId]);
                $matched++;
            }
        }

        return redirect()->route('admin.promo.index')
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

    private function authorizePromoUpload(): void
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && $user->role?->name !== 'headquarters') {
            abort(403, 'Only Headquarters or Super Admin can upload promo lists.');
        }
    }
}
