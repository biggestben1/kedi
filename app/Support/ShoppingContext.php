<?php

namespace App\Support;

use App\Models\AnnexStock;
use App\Models\BranchStock;
use App\Models\HeadquartersStock;
use App\Models\Product;
use App\Models\ServiceCenterStock;
use App\Models\User;

class ShoppingContext
{
    public readonly User $user;

    public readonly User $walletOwner;

    public readonly User $stockOwner;

    public readonly string $stockRole;

    public readonly ?int $branchUserId;

    public readonly bool $isHeadquarters;

    public function __construct(User $user)
    {
        $user->loadMissing(['role', 'createdBy.role']);
        $this->user = $user;
        $this->walletOwner = $user->walletOwnerForShopping();

        $stockOwner = $user;
        $roleName = $user->role?->name ?? '';

        if ($user->isCashierOrDistributor() && $user->createdBy && $user->createdBy->role) {
            $ownerRole = $user->createdBy->role->name;
            if (in_array($ownerRole, ['headquarters', 'branch', 'service_center', 'annex'], true)) {
                $stockOwner = $user->createdBy;
                $roleName = $ownerRole;
            }
        }

        $this->stockOwner = $stockOwner;
        $this->stockRole = $roleName;
        $this->isHeadquarters = $roleName === 'headquarters';
        $this->branchUserId = in_array($roleName, ['branch', 'service_center', 'annex'], true)
            ? (int) $stockOwner->id
            : null;
    }

    public function usesOrgStock(): bool
    {
        return $this->isHeadquarters || $this->branchUserId !== null;
    }

    public function availableStock(int $productId, ?int $fallbackStock = null): int
    {
        if ($this->isHeadquarters) {
            return HeadquartersStock::getQuantity((int) $this->stockOwner->id, $productId);
        }

        if ($this->branchUserId) {
            return match ($this->stockRole) {
                'branch' => BranchStock::getQuantity($this->branchUserId, $productId),
                'service_center' => ServiceCenterStock::getQuantity($this->branchUserId, $productId),
                'annex' => AnnexStock::getQuantity($this->branchUserId, $productId),
                default => BranchStock::getQuantity($this->branchUserId, $productId),
            };
        }

        return $fallbackStock ?? (int) (Product::find($productId)?->stock ?? 0);
    }

    /**
     * @return list<int>
     */
    public function inStockProductIds(): array
    {
        if ($this->isHeadquarters) {
            return HeadquartersStock::query()
                ->where('headquarters_user_id', $this->stockOwner->id)
                ->where('quantity', '>', 0)
                ->pluck('product_id')
                ->all();
        }

        if ($this->branchUserId) {
            return match ($this->stockRole) {
                'branch' => BranchStock::query()
                    ->where('branch_user_id', $this->branchUserId)
                    ->where('quantity', '>', 0)
                    ->pluck('product_id')
                    ->all(),
                'service_center' => ServiceCenterStock::query()
                    ->where('service_center_user_id', $this->branchUserId)
                    ->where('quantity', '>', 0)
                    ->pluck('product_id')
                    ->all(),
                'annex' => AnnexStock::query()
                    ->where('annex_user_id', $this->branchUserId)
                    ->where('quantity', '>', 0)
                    ->pluck('product_id')
                    ->all(),
                default => [],
            };
        }

        return [];
    }
}
