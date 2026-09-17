<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectionCenterMove;
use App\Models\Order;
use App\Models\OrderGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionCenterController extends Controller
{
    public function branches(): JsonResponse
    {
        $branches = $this->branchesQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email'])
            ->map(fn (User $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'phone' => $b->phone,
                'email' => $b->email,
            ]);

        return response()->json(['data' => $branches]);
    }

    public function incoming(Request $request): JsonResponse
    {
        $branch = $this->viewerBranch($request->user());
        if (! $branch) {
            return response()->json(['message' => 'Only a branch can see orders sent for collection.'], 403);
        }

        $orders = Order::with(['user:id,name,email', 'items', 'collectionBranch:id,name'])
            ->where('collection_branch_id', $branch->id)
            ->whereNull('collected_at')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 20));

        $otherBranches = $this->branchesQuery()
            ->where('id', '!=', $branch->id)
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn (User $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'phone' => $b->phone,
            ]);

        $orders->getCollection()->transform(fn (Order $order) => $this->orderResource($order));

        return response()->json([
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
            ],
            'other_branches' => $otherBranches,
            'orders' => $orders,
        ]);
    }

    public function moveOrder(Request $request, Order $order): JsonResponse
    {
        $branch = $this->viewerBranch($request->user());
        $user = $request->user();
        $user->loadMissing('role');
        $seeAll = $user->isSuperAdmin() || $user->role?->name === Role::HEADQUARTERS;

        if (
            ! $order->collection_branch_id
            || $order->collected_at
            || (! $seeAll && ! ($branch && (int) $order->collection_branch_id === (int) $branch->id))
        ) {
            return response()->json(['message' => 'You cannot move this order.'], 403);
        }

        $data = $request->validate([
            'collection_branch_id' => ['required', 'integer'],
        ]);

        $destination = User::where('id', $data['collection_branch_id'])
            ->whereHas('role', fn ($q) => $q->where('name', Role::BRANCH))
            ->first();

        if (! $destination) {
            return response()->json(['message' => 'Choose a valid collection center.'], 422);
        }

        if ((int) $destination->id === (int) $order->collection_branch_id) {
            return response()->json(['message' => 'Order is already at '.$destination->name.'.']);
        }

        $order->loadMissing('collectionBranch');
        $fromId = $order->collection_branch_id ? (int) $order->collection_branch_id : null;
        $fromName = $order->collectionBranch?->name ?: 'current center';

        DB::transaction(function () use ($order, $destination, $fromId, $request) {
            $order->update(['collection_branch_id' => $destination->id]);

            CollectionCenterMove::create([
                'order_id' => $order->id,
                'order_group_id' => $order->order_group_id,
                'from_branch_user_id' => $fromId,
                'to_branch_user_id' => $destination->id,
                'moved_by_user_id' => $request->user()->id,
                'reason' => 'Moved from collection center (mobile)',
            ]);

            if ($order->order_group_id) {
                OrderGroup::where('id', $order->order_group_id)
                    ->update(['collection_branch_id' => $destination->id]);
            }
        });

        $order->load(['items', 'collectionBranch:id,name', 'user:id,name']);

        return response()->json([
            'message' => 'Order '.($order->invoice_number ?: '#'.$order->id).' moved from '.$fromName.' to '.$destination->name.'.',
            'data' => $this->orderResource($order),
        ]);
    }

    private function orderResource(Order $order): array
    {
        return [
            'id' => $order->id,
            'invoice_number' => $order->invoice_number ?? 'ORD-'.$order->id,
            'status' => $order->status,
            'subtotal' => (float) $order->subtotal,
            'kd_id' => $order->kd_id,
            'customer_name' => $order->customer_name ?: $order->user?->name,
            'collection_branch_id' => $order->collection_branch_id,
            'collection_branch' => $order->collectionBranch ? [
                'id' => $order->collectionBranch->id,
                'name' => $order->collectionBranch->name,
            ] : null,
            'collected_at' => $order->collected_at?->toIso8601String(),
            'items' => $order->items->map(fn ($i) => [
                'product_name' => $i->product_name,
                'quantity' => $i->quantity,
                'line_total' => (float) $i->line_total,
            ])->all(),
        ];
    }

    private function branchesQuery()
    {
        return User::whereHas('role', function ($q) {
            $q->where('name', Role::BRANCH);
        });
    }

    private function viewerBranch(?User $user): ?User
    {
        if (! $user) {
            return null;
        }
        $user->loadMissing('role', 'createdBy.role');
        if ($user->role?->name === Role::BRANCH) {
            return $user;
        }
        if (in_array($user->role?->name, [Role::CASHIER, Role::ACCOUNTANT, Role::DISPATCH, Role::DISTRIBUTOR], true)
            && $user->createdBy?->role?->name === Role::BRANCH) {
            return $user->createdBy;
        }

        return null;
    }
}
