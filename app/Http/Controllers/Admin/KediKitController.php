<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KediKit;
use App\Models\KediKitItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KediKitController extends Controller
{
    public function index()
    {
        $kits = KediKit::with(['createdBy', 'purchasedBy', 'items'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.kedi-kits.index', compact('kits'));
    }

    public function create()
    {
        $users = User::with('role')->orderBy('name')->get();
        return view('admin.kedi-kits.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => ['required', 'in:english,french'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'kd_numbers' => ['required', 'array', 'min:1'],
            'kd_numbers.*.kd_no' => ['required', 'string', 'max:100'],
            'kd_numbers.*.is_old' => ['nullable', 'boolean'],
            'kd_numbers.*.purchased_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        DB::beginTransaction();
        try {
            $kit = KediKit::create([
                'category' => $validated['category'],
                'price' => $validated['price'],
                'quantity' => $validated['quantity'],
                'description' => $validated['description'] ?? null,
                'created_by_user_id' => auth()->id(),
            ]);

            foreach ($validated['kd_numbers'] as $kdData) {
                KediKitItem::create([
                    'kedi_kit_id' => $kit->id,
                    'kd_no' => strtoupper(trim($kdData['kd_no'])),
                    'is_old' => $kdData['is_old'] ?? false,
                    'purchased_by_user_id' => $kdData['purchased_by_user_id'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.kedi-kits.index')
                ->with('success', 'KEDI Kit created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create KEDI Kit: ' . $e->getMessage());
        }
    }

    public function show(KediKit $kediKit)
    {
        $kediKit->load(['createdBy', 'purchasedBy', 'items.purchasedBy', 'purchases.buyer', 'purchases.seller', 'purchases.backOrders', 'backOrders.buyer', 'backOrders.purchase']);
        $users = \App\Models\User::with('role')->orderBy('name')->get();
        return view('admin.kedi-kits.show', compact('kediKit', 'users'));
    }

    public function edit(KediKit $kediKit)
    {
        $kediKit->load('items.purchasedBy');
        $users = \App\Models\User::with('role')->orderBy('name')->get();
        return view('admin.kedi-kits.edit', compact('kediKit', 'users'));
    }

    public function update(Request $request, KediKit $kediKit)
    {
        $validated = $request->validate([
            'category' => ['required', 'in:english,french'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'kd_numbers' => ['required', 'array', 'min:1'],
            'kd_numbers.*.kd_no' => ['required', 'string', 'max:100'],
            'kd_numbers.*.is_old' => ['nullable', 'boolean'],
            'kd_numbers.*.purchased_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        DB::beginTransaction();
        try {
            $kediKit->update([
                'category' => $validated['category'],
                'price' => $validated['price'],
                'quantity' => $validated['quantity'],
                'description' => $validated['description'] ?? null,
            ]);

            // Delete existing items
            $kediKit->items()->delete();

            // Create new items
            foreach ($validated['kd_numbers'] as $kdData) {
                KediKitItem::create([
                    'kedi_kit_id' => $kediKit->id,
                    'kd_no' => $kdData['kd_no'],
                    'is_old' => $kdData['is_old'] ?? false,
                    'purchased_by_user_id' => $kdData['purchased_by_user_id'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.kedi-kits.index')
                ->with('success', 'KEDI Kit updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to update KEDI Kit: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, KediKit $kediKit)
    {
        $validated = $request->validate([
            'is_old' => ['required', 'boolean'],
            'purchased_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $kediKit->update([
            'is_old' => $validated['is_old'],
            'purchased_by_user_id' => $validated['purchased_by_user_id'] ?? null,
        ]);

        return back()->with('success', 'Kit status updated successfully.');
    }

    public function addKdNumbers(Request $request, KediKit $kediKit)
    {
        $validated = $request->validate([
            'kd_numbers' => ['required', 'array', 'min:1'],
            'kd_numbers.*.kd_no' => ['required', 'string', 'max:100'],
            'kd_numbers.*.is_old' => ['nullable', 'boolean'],
            'kd_numbers.*.purchased_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['kd_numbers'] as $kdData) {
                KediKitItem::create([
                    'kedi_kit_id' => $kediKit->id,
                    'kd_no' => $kdData['kd_no'],
                    'is_old' => $kdData['is_old'] ?? false,
                    'purchased_by_user_id' => $kdData['purchased_by_user_id'] ?? null,
                ]);
            }

            // Increment quantity of the kit
            $kediKit->increment('quantity', count($validated['kd_numbers']));

            DB::commit();

            return back()->with('success', 'KD numbers added successfully and kit quantity updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to add KD numbers: ' . $e->getMessage());
        }
    }

    public function destroy(KediKit $kediKit)
    {
        DB::beginTransaction();
        try {
            $kediKit->items()->delete();
            $kediKit->delete();

            DB::commit();

            return redirect()->route('admin.kedi-kits.index')
                ->with('success', 'KEDI Kit deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete KEDI Kit: ' . $e->getMessage());
        }
    }
}
