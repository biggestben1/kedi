<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class OrgUserScope
{
    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    public static function extendWithDescendants(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        do {
            $added = User::query()
                ->whereIn('created_by_user_id', $ids)
                ->whereNotIn('id', $ids)
                ->pluck('id')
                ->all();
            $ids = array_merge($ids, $added);
        } while ($added !== []);

        return $ids;
    }

    /**
     * User IDs whose financial/wallet activity the current user may view.
     * Null = unrestricted (super admin, wholesale staff, etc.).
     *
     * @return list<int>|null
     */
    public static function allowedUserIds(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing('role');
        $role = $user->role?->name ?? '';

        if ($role === 'headquarters') {
            $ids = User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['service_center', 'annex', 'branch']));
                })
                ->pluck('id')
                ->all();

            return self::extendWithDescendants($ids);
        }

        if ($role === 'branch') {
            $ids = User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['service_center', 'annex', 'accountant']));
                })
                ->pluck('id')
                ->all();

            return self::extendWithDescendants($ids);
        }

        if ($role === 'service_center') {
            $ids = User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['annex', 'dispatch', 'accountant']));
                })
                ->pluck('id')
                ->all();

            return self::extendWithDescendants($ids);
        }

        if ($role === 'annex') {
            $ids = User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['accountant', 'dispatch', 'cashier', 'distributor']));
                })
                ->pluck('id')
                ->all();

            return self::extendWithDescendants($ids);
        }

        if ($role === 'accountant' && $user->created_by_user_id) {
            $creator = User::with('role')->find($user->created_by_user_id);

            return $creator ? self::allowedUserIds($creator) : null;
        }

        return null;
    }

    /**
     * @return list<int>
     */
    public static function orgOwnerIds(?User $user): array
    {
        $allowed = self::allowedUserIds($user);
        if ($allowed === null) {
            return User::whereHas('role', fn ($r) => $r->whereIn('name', ['headquarters', 'branch', 'service_center', 'annex']))
                ->pluck('id')
                ->all();
        }

        return User::whereIn('id', $allowed)
            ->whereHas('role', fn ($r) => $r->whereIn('name', ['headquarters', 'branch', 'service_center', 'annex']))
            ->pluck('id')
            ->all();
    }

    /**
     * Org owner accounts (HQ, Branch, Service Center, Annex) visible to the user.
     *
     * @return Collection<int, User>
     */
    public static function orgOffices(?User $user): Collection
    {
        $ownerIds = self::orgOwnerIds($user);
        $roleOrder = [
            'headquarters' => 1,
            'branch' => 2,
            'service_center' => 3,
            'annex' => 4,
        ];

        return User::with('role')
            ->whereIn('id', $ownerIds)
            ->get()
            ->sortBy(fn (User $office) => [
                $roleOrder[$office->role?->name ?? 'other'] ?? 99,
                strtolower($office->name ?? ''),
            ])
            ->values();
    }

    public static function orgUnitLabel(?User $user): string
    {
        return match ($user?->role?->name) {
            'headquarters' => 'Headquarters',
            'branch' => 'Branch',
            'service_center' => 'Service Center',
            'annex' => 'Annex',
            default => 'Office',
        };
    }

    /**
     * Resolve allowed user IDs, optionally narrowed to one office and its staff.
     *
     * @return list<int>|null
     */
    public static function scopeForOfficeFilter(?User $user, ?int $officeId): ?array
    {
        $allowed = self::allowedUserIds($user);

        if ($officeId === null) {
            return $allowed;
        }

        $ownerIds = self::orgOwnerIds($user);
        if (! in_array($officeId, $ownerIds, true)) {
            abort(403, 'You cannot view reports for that office.');
        }

        return self::extendWithDescendants([$officeId]);
    }

    public static function resolveOrgUnit(?User $user): string
    {
        if (! $user) {
            return 'other';
        }

        $user->loadMissing(['role', 'createdBy.role']);
        $role = $user->role?->name ?? '';

        if (in_array($role, ['headquarters', 'branch', 'service_center', 'annex'], true)) {
            return $role;
        }

        if ($user->createdBy) {
            return self::resolveOrgUnit($user->createdBy);
        }

        return 'other';
    }

    public static function orgOwnerForUserCreation(User $creator, string $newRoleName): int
    {
        if (in_array($newRoleName, ['cashier', 'distributor'], true)) {
            $owner = self::walkToOrgOwner($creator);

            return $owner?->id ?? $creator->id;
        }

        return $creator->id;
    }

    private static function walkToOrgOwner(User $user): ?User
    {
        $user->loadMissing(['role', 'createdBy.role']);
        $role = $user->role?->name ?? '';

        if (in_array($role, ['headquarters', 'branch', 'service_center', 'annex'], true)) {
            return $user;
        }

        if ($user->createdBy) {
            return self::walkToOrgOwner($user->createdBy);
        }

        return null;
    }
}
