<?php

namespace App\Http\Controllers;

use App\Mail\AnnexWelcomeMail;
use App\Mail\ServiceCenterWelcomeMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SuperAdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $roleName = $request->query('role');
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';

        $branchOnly = $request->user()?->role?->name === 'branch';
        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';
        $annexOnly = $request->user()?->role?->name === 'annex';

        // Branch: can see users they created (Annex, Service Center, Accountant, Dispatch)
        if ($branchOnly) {
            if ($roleName && ! in_array($roleName, ['annex', 'service_center', 'accountant', 'dispatch'], true)) {
                return redirect()->route('admin.users.index', ['role' => $roleName, 'q' => $q])->with('error', 'Access denied.');
            }
            $roleName = $roleName ?: null;
            $createdBy = $request->user()->id;
        }

        // Service Center: can see users they created (Annex, Dispatch, Accountant)
        if ($serviceCenterOnly) {
            if ($roleName && ! in_array($roleName, ['annex', 'dispatch', 'accountant'], true)) {
                return redirect()->route('admin.users.index', ['role' => $roleName, 'q' => $q])->with('error', 'Access denied.');
            }
            $roleName = $roleName ?: null;
            $createdBy = $request->user()->id;
        }

        // Annex: can see users they created (Accountant, Dispatch)
        if ($annexOnly) {
            if ($roleName && ! in_array($roleName, ['accountant', 'dispatch'], true)) {
                return redirect()->route('admin.users.index', ['role' => $roleName, 'q' => $q])->with('error', 'Access denied.');
            }
            $roleName = $roleName ?: null;
            $createdBy = $request->user()->id;
        }

        // Headquarters: only users they created (Branch, Annex, Service Center, Accountant)
        if ($headquartersOnly) {
            $roleName = $roleName ?: null;
            if ($roleName && ! in_array($roleName, ['branch', 'annex', 'service_center', 'accountant', 'dispatch'], true)) {
                return redirect()->route('admin.users.index', ['role' => $roleName, 'q' => $q])->with('error', 'Access denied.');
            }
            // Automatically set created_by to current user for headquarters - don't require it in URL
            $createdBy = $request->user()->id;
        }

        // Reseller: only their customers (force role=customer, created_by=self)
        if ($resellerOnly) {
            $roleName = 'customer';
            // Automatically set created_by to current user for reseller - don't require it in URL
            $createdBy = $request->user()->id;
        }

        // Prevent wholesale_staff from filtering by super_admin, wholesale_staff, accountant, dispatch
        if ($wholesaleOnly && in_array($roleName, ['super_admin', 'wholesale_staff', 'accountant', 'dispatch'], true)) {
            return redirect()->route('admin.users.index', ['q' => $q])->with('error', 'Access denied.');
        }

        $currentUserId = $request->user()?->id;

        // Only get createdBy from query if not already set by role-specific logic above
        if (!isset($createdBy)) {
            $createdBy = $request->query('created_by');
        }
        
        $createdByUser = null;
        if ($createdBy) {
            $createdByUser = User::with('role')->find($createdBy);
            // Wholesale staff may only view customers of resellers they created
            if ($wholesaleOnly && $createdByUser && (int) $createdByUser->created_by_user_id !== (int) $currentUserId) {
                return redirect()->route('admin.users.index', ['role' => 'reseller'])->with('error', 'Access denied.');
            }
        }

        $users = User::query()
            ->with('role', 'createdBy')
            ->withSum('dpbvCollections', 'dpbv')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when($roleName !== null && $roleName !== '', function ($query) use ($roleName) {
                $query->whereHas('role', fn ($r) => $r->where('name', $roleName));
            })
            ->when($createdBy !== null && $createdBy !== '', function ($query) use ($createdBy, $wholesaleOnly, $currentUserId) {
                $query->where('created_by_user_id', $createdBy);
            })
            ->when($wholesaleOnly && ($createdBy === null || $createdBy === ''), function ($query) use ($currentUserId) {
                // Wholesale staff list: only reseller/customer; only users they created (their own)
                $query->whereHas('role', fn ($r) => $r->whereIn('name', ['reseller', 'customer']))
                    ->where('created_by_user_id', $currentUserId);
            })
            ->when($resellerOnly, function ($query) use ($currentUserId) {
                // Reseller: only their customers
                $query->where('created_by_user_id', $currentUserId);
            })
            ->when($branchOnly, function ($query) use ($currentUserId, $roleName) {
                // Branch: only users they created (Annex, Service Center, Accountant, Dispatch)
                $query->where('created_by_user_id', $currentUserId)
                    ->whereHas('role', function ($r) use ($roleName) {
                        if ($roleName && in_array($roleName, ['annex', 'service_center', 'accountant', 'dispatch'], true)) {
                            $r->where('name', $roleName);
                        } else {
                            $r->whereIn('name', ['annex', 'service_center', 'accountant', 'dispatch']);
                        }
                    });
            })
            ->when($serviceCenterOnly, function ($query) use ($currentUserId, $roleName) {
                // Service Center: only users they created (Annex, Dispatch, Accountant)
                $query->where('created_by_user_id', $currentUserId)
                    ->whereHas('role', function ($r) use ($roleName) {
                        if ($roleName && in_array($roleName, ['annex', 'dispatch', 'accountant'], true)) {
                            $r->where('name', $roleName);
                        } else {
                            $r->whereIn('name', ['annex', 'dispatch', 'accountant']);
                        }
                    });
            })
            ->when($annexOnly, function ($query) use ($currentUserId, $roleName) {
                // Annex: only users they created (Accountant, Dispatch)
                $query->where('created_by_user_id', $currentUserId)
                    ->whereHas('role', function ($r) use ($roleName) {
                        if ($roleName && in_array($roleName, ['accountant', 'dispatch'], true)) {
                            $r->where('name', $roleName);
                        } else {
                            $r->whereIn('name', ['accountant', 'dispatch']);
                        }
                    });
            })
            ->when($headquartersOnly, function ($query) use ($currentUserId, $roleName) {
                // Headquarters: only users they created (Branch, Annex, Service Center, Accountant)
                $query->where('created_by_user_id', $currentUserId)
                    ->whereHas('role', function ($r) use ($roleName) {
                        if ($roleName && in_array($roleName, ['branch', 'annex', 'service_center', 'accountant'], true)) {
                            $r->where('name', $roleName);
                        } else {
                            $r->whereIn('name', ['branch', 'annex', 'service_center', 'accountant']);
                        }
                    });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // For branch viewing admin, set roleFilter to null
        if ($branchOnly && $request->query('view_admin') === '1') {
            $roleName = null;
        }

        return view('admin.users.index', [
            'users' => $users,
            'q' => $q,
            'roleFilter' => $roleName,
            'createdByUser' => $createdByUser,
        ]);
    }

    public function create(Request $request)
    {
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        $branchOnly = $request->user()?->role?->name === 'branch';
        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';
        $annexOnly = $request->user()?->role?->name === 'annex';
        $isSuperAdmin = $request->user()?->isSuperAdmin();
        $roles = Role::orderBy('display_name');
        if ($isSuperAdmin) {
            // Super admin: exclude wholesale_staff, reseller, customer
            $roles->whereNotIn('name', ['wholesale_staff', 'reseller', 'customer']);
        }
        if ($wholesaleOnly) {
            $roles->whereNotIn('name', ['super_admin', 'wholesale_staff', 'accountant', 'dispatch']);
        }
        if ($resellerOnly) {
            $roles->where('name', 'customer');
        }
        if ($headquartersOnly) {
            $roles->whereIn('name', ['branch', 'annex', 'service_center', 'accountant', 'dispatch']);
        }
        if ($branchOnly) {
            $roles->whereIn('name', ['annex', 'service_center', 'accountant', 'dispatch']);
        }
        if ($serviceCenterOnly) {
            $roles->whereIn('name', ['annex', 'dispatch', 'accountant']);
        }
        if ($annexOnly) {
            $roles->whereIn('name', ['accountant', 'dispatch']);
        }
        $roles = $roles->get();

        // Pre-select role from query (e.g. ?role=annex for "Create Annex" shortcut)
        $defaultRoleId = null;
        $roleParam = $request->query('role');
        if ($roleParam && $roles->contains('name', $roleParam)) {
            $defaultRoleId = $roles->firstWhere('name', $roleParam)?->id;
        }

        return view('admin.users.create', [
            'roles' => $roles,
            'defaultRoleId' => $defaultRoleId,
        ]);
    }

    public function store(Request $request)
    {
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        $branchOnly = $request->user()?->role?->name === 'branch';
        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';
        $isSuperAdmin = $request->user()?->isSuperAdmin();
        $roles = Role::query();
        if ($isSuperAdmin) {
            // Super admin: exclude wholesale_staff, reseller, customer
            $roles->whereNotIn('name', ['wholesale_staff', 'reseller', 'customer']);
        }
        if ($wholesaleOnly) {
            $roles->whereNotIn('name', ['super_admin', 'wholesale_staff', 'accountant', 'dispatch']);
        }
        if ($resellerOnly) {
            $roles->where('name', 'customer');
        }
        if ($headquartersOnly) {
            $roles->whereIn('name', ['branch', 'annex', 'service_center', 'accountant', 'dispatch']);
        }
        if ($branchOnly) {
            $roles->whereIn('name', ['annex', 'service_center', 'accountant', 'dispatch']);
        }
        if ($serviceCenterOnly) {
            $roles->whereIn('name', ['annex', 'dispatch', 'accountant']);
        }
        $allowedRoleIds = $roles->pluck('id')->all();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
            'service_center_code' => ['nullable', 'string', 'max:255'],
            'role_id' => ['required', 'integer', Rule::in($allowedRoleIds)],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
        if ($resellerOnly) {
            $rules['kid'] = ['nullable', 'string', 'max:255'];
        }
        $data = $request->validate($rules);

        $createData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'service_center_code' => $data['service_center_code'] ?? null,
            'role_id' => $data['role_id'],
            'password' => Hash::make($data['password']),
        ];
        if ($resellerOnly && array_key_exists('kid', $data)) {
            $createData['kid'] = $data['kid'] ?: null;
        }
        if ($wholesaleOnly || $resellerOnly || $headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) {
            $createData['created_by_user_id'] = $request->user()->id;
        }
        $user = User::create($createData);

        // Get the user's role
        $userRole = $user->load('role')->role;
        
        // Send welcome email to service center users
        if ($userRole && $userRole->name === 'service_center') {
            try {
                Log::info('Sending service center welcome email to: ' . $user->email);
                Mail::to($user->email)->send(new ServiceCenterWelcomeMail($user, $data['password']));
                Log::info('Service center welcome email sent successfully to: ' . $user->email);
            } catch (\Exception $e) {
                Log::error('Failed to send service center welcome email to ' . $user->email . ': ' . $e->getMessage());
            }
        }
        
        // Send welcome email to annex users
        if ($userRole && $userRole->name === 'annex') {
            try {
                Log::info('Sending annex welcome email to: ' . $user->email);
                Mail::to($user->email)->send(new AnnexWelcomeMail($user, $data['password']));
                Log::info('Annex welcome email sent successfully to: ' . $user->email);
            } catch (\Exception $e) {
                Log::error('Failed to send annex welcome email to ' . $user->email . ': ' . $e->getMessage());
            }
        }

        if ($resellerOnly) {
            return redirect()->route('admin.users.index', ['role' => 'customer', 'created_by' => $request->user()->id])->with('success', 'Customer created successfully.');
        }
        if ($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) {
            $redirectParams = ['created_by' => $request->user()->id];
            if ($branchOnly || $serviceCenterOnly) {
                $redirectParams['role'] = 'annex';
            }
            return redirect()->route('admin.users.index', $redirectParams)->with('success', 'User created successfully.');
        }
        return redirect()->route('admin.users.create')->with('success', 'User account created successfully.');
    }

    public function edit(Request $request, User $user)
    {
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        $branchOnly = $request->user()?->role?->name === 'branch';
        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';
        $annexOnly = $request->user()?->role?->name === 'annex';
        $isSuperAdmin = $request->user()?->isSuperAdmin();

        // Super admin, headquarters, and branch can edit any user (not just those they created)
        $canEditAny = $isSuperAdmin || $headquartersOnly || $branchOnly;
        if (!$canEditAny && ($wholesaleOnly || $resellerOnly || $serviceCenterOnly || $annexOnly)) {
            if ($user->created_by_user_id != $request->user()->id) {
                abort(403, 'Access denied. You can only edit users you created.');
            }
        }

        $roles = Role::orderBy('display_name');
        if ($isSuperAdmin) {
            // Super admin: exclude wholesale_staff, reseller, customer
            $roles->whereNotIn('name', ['wholesale_staff', 'reseller', 'customer']);
        }
        if ($wholesaleOnly) {
            $roles->whereNotIn('name', ['super_admin', 'wholesale_staff', 'accountant', 'dispatch']);
        }
        if ($resellerOnly) {
            $roles->where('name', 'customer');
        }
        if ($headquartersOnly) {
            $roles->whereIn('name', ['branch', 'annex', 'service_center', 'accountant', 'dispatch']);
        }
        if ($branchOnly) {
            $roles->whereIn('name', ['annex', 'service_center', 'accountant', 'dispatch']);
        }
        if ($serviceCenterOnly) {
            $roles->whereIn('name', ['annex', 'dispatch', 'accountant']);
        }
        if ($annexOnly) {
            $roles->whereIn('name', ['accountant', 'dispatch']);
        }
        $roles = $roles->get();

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        $branchOnly = $request->user()?->role?->name === 'branch';
        $isSuperAdmin = $request->user()?->isSuperAdmin();

        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';
        $annexOnly = $request->user()?->role?->name === 'annex';
        // Super admin, headquarters, and branch can edit any user (not just those they created)
        $canEditAny = $isSuperAdmin || $headquartersOnly || $branchOnly;
        if (!$canEditAny && ($wholesaleOnly || $resellerOnly || $serviceCenterOnly || $annexOnly) && $user->created_by_user_id !== $request->user()->id) {
            abort(403, 'Access denied. You can only edit users you created.');
        }

        $roles = Role::query();
        if ($isSuperAdmin) {
            // Super admin: exclude wholesale_staff, reseller, customer
            $roles->whereNotIn('name', ['wholesale_staff', 'reseller', 'customer']);
        }
        if ($wholesaleOnly) {
            $roles->whereNotIn('name', ['super_admin', 'wholesale_staff', 'accountant', 'dispatch']);
        }
        if ($resellerOnly) {
            $roles->where('name', 'customer');
        }
        if ($headquartersOnly) {
            $roles->whereIn('name', ['branch', 'annex', 'service_center', 'accountant', 'dispatch']);
        }
        if ($branchOnly) {
            $roles->whereIn('name', ['annex', 'service_center', 'accountant', 'dispatch']);
        }
        if ($serviceCenterOnly) {
            $roles->whereIn('name', ['annex', 'dispatch', 'accountant']);
        }
        if ($annexOnly) {
            $roles->whereIn('name', ['accountant', 'dispatch']);
        }
        $allowedRoleIds = array_map('strval', $roles->pluck('id')->all());

        $updateRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'service_center_code' => ['nullable', 'string', 'max:255'],
            'role_id' => ['required', 'integer', Rule::in($allowedRoleIds)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ];
        if ($resellerOnly) {
            $updateRules['kid'] = ['nullable', 'string', 'max:255'];
        }
        $data = $request->validate($updateRules);

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'service_center_code' => $data['service_center_code'] ?? null,
            'role_id' => $data['role_id'],
        ];
        if ($resellerOnly && array_key_exists('kid', $data)) {
            $update['kid'] = $data['kid'] ?: null;
        }

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);
        if ($resellerOnly) {
            return redirect()->route('admin.users.index', ['role' => 'customer', 'created_by' => $request->user()->id])->with('success', 'User updated.');
        }
        if ($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) {
            return redirect()->route('admin.users.index', ['created_by' => $request->user()->id])->with('success', 'User updated.');
        }
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user)
    {
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        $branchOnly = $request->user()?->role?->name === 'branch';
        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';
        $annexOnly = $request->user()?->role?->name === 'annex';

        if (($resellerOnly || $headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) && $user->created_by_user_id != $request->user()->id) {
            abort(403, 'Access denied. You can only delete users you created.');
        }
        // Safety: prevent deleting yourself
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Wholesale staff can only delete users they created
        if ($wholesaleOnly && $user->created_by_user_id !== $request->user()->id) {
            return back()->with('error', 'Access denied. You can only delete users you created.');
        }

        $user->delete();

        if ($resellerOnly) {
            return redirect()->route('admin.users.index', ['role' => 'customer', 'created_by' => $request->user()->id])->with('success', 'User deleted.');
        }
        if ($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) {
            return redirect()->route('admin.users.index', ['created_by' => $request->user()->id])->with('success', 'User deleted.');
        }
        return back()->with('success', 'User deleted.');
    }
}

