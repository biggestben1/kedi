<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AnnexWelcomeMail;
use App\Mail\ServiceCenterWelcomeMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class SuperAdminServiceCenterAnnexController extends Controller
{
    public function create()
    {
        $roles = Role::whereIn('name', ['service_center', 'annex'])->get();
        return view('admin.service-center-annex.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $allowedRoleIds = Role::whereIn('name', ['service_center', 'annex'])->pluck('id')->all();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'service_center_code' => 'required|string|max:255',
            'role_id' => ['required', 'integer', Rule::in($allowedRoleIds)],
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'service_center_code' => $data['service_center_code'],
            'role_id' => $data['role_id'],
            'password' => Hash::make($data['password']),
            'created_by_user_id' => User::whereHas('role', fn($r) => $r->where('name', 'super_admin'))->first()?->id,
        ]);

        // Send welcome email
        $userRole = Role::find($data['role_id']);
        if ($userRole->name === 'service_center') {
            try {
                Mail::to($user->email)->send(new ServiceCenterWelcomeMail($user, $data['password']));
            } catch (\Exception $e) {
                Log::error('Failed to send SC welcome email: ' . $e->getMessage());
            }
        } elseif ($userRole->name === 'annex') {
            try {
                Mail::to($user->email)->send(new AnnexWelcomeMail($user, $data['password']));
            } catch (\Exception $e) {
                Log::error('Failed to send Annex welcome email: ' . $e->getMessage());
            }
        }

        return redirect()->route('login')
            ->with('success', 'Registration successful! You can now log in to your account.');
    }
}
