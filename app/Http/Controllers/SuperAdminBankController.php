<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Http\Request;

class SuperAdminBankController extends Controller
{
    /**
     * For HQ: their id. For branch: their HQ's id (or 0 if no HQ = see no banks). For branch accountant: their branch's HQ id (or 0).
     * For service_center: their Branch's HQ id (or 0). For accountant created by service_center: same. Otherwise null (can see all).
     */
    private function getAllowedHqIdForBanks(User $user): ?int
    {
        if (! $user->relationLoaded('role')) {
            $user->load('role');
        }
        $role = $user->role?->name;
        if ($role === 'headquarters') {
            return (int) $user->id;
        }
        if ($role === 'branch') {
            return $user->created_by_user_id ? (int) $user->created_by_user_id : 0;
        }
        if ($role === 'service_center') {
            $branch = $user->created_by_user_id ? User::find($user->created_by_user_id) : null;
            return ($branch && $branch->created_by_user_id) ? (int) $branch->created_by_user_id : 0;
        }
        if ($role === 'accountant' && $user->created_by_user_id) {
            $creator = User::with('role')->find($user->created_by_user_id);
            if ($creator && $creator->role?->name === 'branch') {
                return $creator->created_by_user_id ? (int) $creator->created_by_user_id : 0;
            }
            if ($creator && $creator->role?->name === 'service_center') {
                $branch = $creator->created_by_user_id ? User::find($creator->created_by_user_id) : null;
                return ($branch && $branch->created_by_user_id) ? (int) $branch->created_by_user_id : 0;
            }
        }
        return null;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $allowedHqId = $this->getAllowedHqIdForBanks($user);

        $query = Bank::query();
        if ($allowedHqId === 0) {
            $query->whereRaw('1 = 0');
        } elseif ($allowedHqId !== null) {
            $query->where('headquarters_user_id', $allowedHqId);
        }
        // null = super_admin or HQ accountant → see all; 0 = branch/branch accountant with no HQ → see none

        $banks = $query->orderBy('name')->get();

        return view('admin.banks.index', ['banks' => $banks]);
    }

    public function create()
    {
        return view('admin.banks.create');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $headquartersOnly = $user->role?->name === 'headquarters';
        $branchOnly = $user->role?->name === 'branch';
        $serviceCenterOnly = $user->role?->name === 'service_center';
        $allowedHqId = $this->getAllowedHqIdForBanks($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        if ($headquartersOnly) {
            $data['headquarters_user_id'] = $user->id;
        }
        if ($branchOnly) {
            if (! $user->created_by_user_id) {
                return redirect()->route('admin.banks.index')->with('error', 'Branch must be linked to a headquarters to manage banks.');
            }
            $data['headquarters_user_id'] = $user->created_by_user_id;
        }
        if ($serviceCenterOnly && $allowedHqId !== null && $allowedHqId > 0) {
            $data['headquarters_user_id'] = $allowedHqId;
        }
        // Branch accountant / Service Center accountant: can create banks under their scope's HQ
        if ($user->role?->name === 'accountant' && $allowedHqId !== null && $allowedHqId > 0) {
            $data['headquarters_user_id'] = $allowedHqId;
        }

        Bank::create($data);
        return redirect()->route('admin.banks.index')->with('success', 'Bank created.');
    }

    public function edit(Request $request, Bank $bank)
    {
        $user = $request->user();
        $allowedHqId = $this->getAllowedHqIdForBanks($user);
        if ($allowedHqId === 0 || ($allowedHqId !== null && (int) $bank->headquarters_user_id !== $allowedHqId)) {
            abort(403, 'You can only edit banks for your scope.');
        }
        return view('admin.banks.edit', ['bank' => $bank]);
    }

    public function update(Request $request, Bank $bank)
    {
        $user = $request->user();
        $allowedHqId = $this->getAllowedHqIdForBanks($user);
        if ($allowedHqId === 0 || ($allowedHqId !== null && (int) $bank->headquarters_user_id !== $allowedHqId)) {
            abort(403, 'You can only update banks for your scope.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $bank->update($data);
        return redirect()->route('admin.banks.index')->with('success', 'Bank updated.');
    }

    public function deactivate(Request $request, Bank $bank)
    {
        $user = $request->user();
        $allowedHqId = $this->getAllowedHqIdForBanks($user);
        if ($allowedHqId === 0 || ($allowedHqId !== null && (int) $bank->headquarters_user_id !== $allowedHqId)) {
            abort(403, 'You can only deactivate banks for your scope.');
        }

        $bank->update(['is_active' => false]);
        return redirect()->route('admin.banks.edit', $bank)->with('success', 'Bank deactivated.');
    }

    public function activate(Request $request, Bank $bank)
    {
        $user = $request->user();
        $allowedHqId = $this->getAllowedHqIdForBanks($user);
        if ($allowedHqId === 0 || ($allowedHqId !== null && (int) $bank->headquarters_user_id !== $allowedHqId)) {
            abort(403, 'You can only activate banks for your scope.');
        }

        $bank->update(['is_active' => true]);
        return redirect()->route('admin.banks.edit', $bank)->with('success', 'Bank activated.');
    }

    public function destroy(Request $request, Bank $bank)
    {
        $user = $request->user();
        $allowedHqId = $this->getAllowedHqIdForBanks($user);
        if ($allowedHqId === 0 || ($allowedHqId !== null && (int) $bank->headquarters_user_id !== $allowedHqId)) {
            abort(403, 'You can only delete banks for your scope.');
        }

        $bank->delete();
        return redirect()->route('admin.banks.index')->with('success', 'Bank deleted.');
    }
}
