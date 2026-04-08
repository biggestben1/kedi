<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminStaffRegistrationController extends Controller
{
    public function create()
    {
        $roles = Role::whereIn('name', ['cashier', 'hr', 'accountant'])->get();
        return view('admin.staff.register', compact('roles'));
    }

    public function store(Request $request)
    {
        $allowedRoleIds = Role::whereIn('name', ['cashier', 'hr', 'accountant'])->pluck('id')->all();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'role_id' => ['required', 'integer', Rule::in($allowedRoleIds)],
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role_id' => $data['role_id'],
            'password' => Hash::make($data['password']),
            'created_by_user_id' => User::whereHas('role', fn($r) => $r->where('name', 'super_admin'))->first()?->id,
        ]);

        return redirect()->route('login')
            ->with('success', 'Staff registration successful! You can now log in to your account.');
    }
}
