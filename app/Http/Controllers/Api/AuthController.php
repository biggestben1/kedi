<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\OrgUserScope;
use App\Support\ShoppingContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->where('name', 'mobile')->delete();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userResource($user),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
        ]);

        $customerRoleId = Role::where('name', Role::CUSTOMER)->first()?->id;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->input('phone'),
            'role_id' => $customerRoleId,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userResource($user),
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userResource($request->user()->load('role')),
        ]);
    }

    private function userResource(User $user): array
    {
        $user->loadMissing(['role', 'createdBy.role']);
        $context = new ShoppingContext($user);
        $walletOwner = $context->walletOwner;

        $parentOffice = null;
        if ($user->isCashier() && $user->createdBy) {
            $parentOffice = [
                'id' => $user->createdBy->id,
                'name' => $user->createdBy->name,
                'role' => $user->createdBy->role?->name,
                'role_label' => OrgUserScope::orgUnitLabel($user->createdBy),
            ];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role?->name,
            'role_label' => match ($user->role?->name) {
                'cashier' => 'Cashier',
                'distributor' => 'Distributor',
                default => ucfirst(str_replace('_', ' ', $user->role?->name ?? 'customer')),
            },
            'is_cashier' => $user->isCashier(),
            'is_distributor' => $user->isDistributor(),
            'wallet_balance' => (float) ($walletOwner->wallet_balance ?? 0),
            'wallet_owner_id' => $walletOwner->id,
            'uses_parent_wallet' => $walletOwner->id !== $user->id,
            'parent_office' => $parentOffice,
            'uses_org_stock' => $context->usesOrgStock(),
        ];
    }
}
