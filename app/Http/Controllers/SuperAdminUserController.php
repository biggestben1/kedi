<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $roleName = $request->query('role');
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $resellerOnly = $request->user()?->role?->name === 'reseller';

        // Reseller: only their customers (force role=customer, created_by=self)
        if ($resellerOnly) {
            $roleName = 'customer';
            $createdBy = $request->query('created_by');
            if ((string) $createdBy !== (string) $request->user()->id) {
                return redirect()->route('admin.users.index', ['role' => 'customer', 'created_by' => $request->user()->id, 'q' => $q]);
            }
        }

        // Prevent wholesale_staff from filtering by super_admin, wholesale_staff, accountant, or dispatch
        if ($wholesaleOnly && in_array($roleName, ['super_admin', 'wholesale_staff', 'accountant', 'dispatch'], true)) {
            return redirect()->route('admin.users.index', ['q' => $q])->with('error', 'Access denied.');
        }

        $currentUserId = $request->user()?->id;

        $createdBy = $request->query('created_by');
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
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

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
        $roles = Role::orderBy('display_name');
        if ($wholesaleOnly) {
            $roles->whereNotIn('name', ['super_admin', 'wholesale_staff', 'accountant', 'dispatch']);
        }
        if ($resellerOnly) {
            $roles->where('name', 'customer');
        }
        $roles = $roles->get();

        return view('admin.users.create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $roles = Role::query();
        if ($wholesaleOnly) {
            $roles->whereNotIn('name', ['super_admin', 'wholesale_staff', 'accountant', 'dispatch']);
        }
        if ($resellerOnly) {
            $roles->where('name', 'customer');
        }
        $allowedRoleIds = $roles->pluck('id')->all();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
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
            'role_id' => $data['role_id'],
            'password' => Hash::make($data['password']),
        ];
        if ($resellerOnly && array_key_exists('kid', $data)) {
            $createData['kid'] = $data['kid'] ?: null;
        }
        if ($wholesaleOnly || $resellerOnly) {
            $createData['created_by_user_id'] = $request->user()->id;
        }
        User::create($createData);

        if ($resellerOnly) {
            return redirect()->route('admin.users.index', ['role' => 'customer', 'created_by' => $request->user()->id])->with('success', 'Customer created successfully.');
        }
        return redirect()->route('admin.users.create')->with('success', 'User account created successfully.');
    }

    public function edit(Request $request, User $user)
    {
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $resellerOnly = $request->user()?->role?->name === 'reseller';

        if ($wholesaleOnly || $resellerOnly) {
            if ($user->created_by_user_id !== $request->user()->id) {
                abort(403, 'Access denied. You can only edit users you created.');
            }
        }

        $roles = Role::orderBy('display_name');
        if ($wholesaleOnly) {
            $roles->whereNotIn('name', ['super_admin', 'wholesale_staff', 'accountant', 'dispatch']);
        }
        if ($resellerOnly) {
            $roles->where('name', 'customer');
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

        if (($wholesaleOnly || $resellerOnly) && $user->created_by_user_id !== $request->user()->id) {
            abort(403, 'Access denied. You can only edit users you created.');
        }

        $roles = Role::query();
        if ($wholesaleOnly) {
            $roles->whereNotIn('name', ['super_admin', 'wholesale_staff', 'accountant', 'dispatch']);
        }
        if ($resellerOnly) {
            $roles->where('name', 'customer');
        }
        $allowedRoleIds = $roles->pluck('id')->all();

        $updateRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
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
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user)
    {
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $resellerOnly = $request->user()?->role?->name === 'reseller';

        if ($resellerOnly && $user->created_by_user_id !== $request->user()->id) {
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
        return back()->with('success', 'User deleted.');
    }
}

