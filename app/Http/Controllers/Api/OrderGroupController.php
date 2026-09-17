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

class OrderGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = $request->user()
            ->orderGroups()
            ->withCount(['orders', 'draftOrders'])
            ->with(['collectionBranch:id,name,phone'])
            ->withSum('orders', 'subtotal')
            ->latest()
            ->paginate($request->input('per_page', 20));

        $groups->getCollection()->transform(fn (OrderGroup $group) => $this->groupResource($group));

        return response()->json($groups);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $group = OrderGroup::create([
            'user_id' => $request->user()->id,
            'name' => trim($data['name']),
            'status' => OrderGroup::STATUS_OPEN,
            'started_at' => now(),
            'total_amount' => 0,
        ]);

        return response()->json([
            'message' => 'Order group created.',
            'data' => $this->groupResource($group->load('collectionBranch')),
        ], 201);
    }

    public function show(Request $request, OrderGroup $orderGroup): JsonResponse
    {
        $this->authorizeGroup($request, $orderGroup);

        $orderGroup->load([
            'collectionBranch:id,name,phone,email',
            'orders' => fn ($q) => $q->with(['items', 'collectionBranch:id,name'])->latest(),
        ]);

        $branches = User::whereHas('role', fn ($q) => $q->where('name', Role::BRANCH))
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email'])
            ->map(fn (User $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'phone' => $b->phone,
                'email' => $b->email,
            ]);

        return response()->json([
            'data' => $this->groupResource($orderGroup, true),
            'collection_branches' => $branches,
        ]);
    }

    public function moveCollectionCenter(Request $request, OrderGroup $orderGroup): JsonResponse
    {
        $this->authorizeGroup($request, $orderGroup);

        $data = $request->validate([
            'collection_branch_id' => ['required', 'integer'],
        ]);

        $branch = User::where('id', $data['collection_branch_id'])
            ->whereHas('role', fn ($q) => $q->where('name', Role::BRANCH))
            ->first();

        if (! $branch) {
            return response()->json(['message' => 'Choose a valid collection center (branch).'], 422);
        }

        $orders = $orderGroup->orders()
            ->whereNull('collected_at')
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'No movable orders in this group. Collected or cancelled orders cannot be moved.',
            ], 422);
        }

        $blocked = $orders->filter(fn (Order $order) => $order->stock_deducted_at && ! $order->collection_branch_id);
        if ($blocked->isNotEmpty()) {
            return response()->json([
                'message' => 'Cannot move '.$blocked->count().' order(s) that already had stock deducted without a collection center.',
            ], 422);
        }

        $moved = 0;
        $moverId = $request->user()->id;
        DB::transaction(function () use ($orders, $branch, $orderGroup, $moverId, &$moved) {
            $orderGroup->update(['collection_branch_id' => $branch->id]);

            foreach ($orders as $order) {
                $fromId = $order->collection_branch_id ? (int) $order->collection_branch_id : null;
                if ($fromId === (int) $branch->id) {
                    continue;
                }

                $order->update(['collection_branch_id' => $branch->id]);

                CollectionCenterMove::create([
                    'order_id' => $order->id,
                    'order_group_id' => $orderGroup->id,
                    'from_branch_user_id' => $fromId,
                    'to_branch_user_id' => $branch->id,
                    'moved_by_user_id' => $moverId,
                    'reason' => 'Moved via order group (mobile)',
                ]);

                $moved++;
            }
        });

        $orderGroup->load(['collectionBranch:id,name,phone', 'orders.items', 'orders.collectionBranch:id,name']);

        return response()->json([
            'message' => $moved === 0
                ? 'All orders are already at '.$branch->name.'.'
                : $moved.' order(s) moved to collection center "'.$branch->name.'".',
            'moved' => $moved,
            'data' => $this->groupResource($orderGroup, true),
        ]);
    }

    private function authorizeGroup(Request $request, OrderGroup $orderGroup): void
    {
        if ((int) $orderGroup->user_id !== (int) $request->user()->id) {
            abort(404);
        }
    }

    private function groupResource(OrderGroup $group, bool $withOrders = false): array
    {
        $data = [
            'id' => $group->id,
            'name' => $group->displayName(),
            'status' => $group->status,
            'total_amount' => (float) ($group->total_amount ?? 0),
            'orders_count' => $group->orders_count ?? $group->orders()->count(),
            'draft_orders_count' => $group->draft_orders_count ?? $group->draftOrders()->count(),
            'orders_sum_subtotal' => (float) ($group->orders_sum_subtotal ?? $group->orders()->sum('subtotal')),
            'collection_branch_id' => $group->collection_branch_id,
            'collection_branch' => $group->collectionBranch ? [
                'id' => $group->collectionBranch->id,
                'name' => $group->collectionBranch->name,
                'phone' => $group->collectionBranch->phone,
            ] : null,
            'started_at' => $group->started_at?->toIso8601String(),
            'completed_at' => $group->completed_at?->toIso8601String(),
            'created_at' => $group->created_at?->toIso8601String(),
        ];

        if ($withOrders) {
            $data['orders'] = $group->orders->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'invoice_number' => $order->invoice_number ?? 'ORD-'.$order->id,
                    'status' => $order->status,
                    'subtotal' => (float) $order->subtotal,
                    'kd_id' => $order->kd_id,
                    'customer_name' => $order->customer_name,
                    'collected_at' => $order->collected_at?->toIso8601String(),
                    'collection_branch_id' => $order->collection_branch_id,
                    'collection_branch' => $order->collectionBranch ? [
                        'id' => $order->collectionBranch->id,
                        'name' => $order->collectionBranch->name,
                    ] : null,
                    'is_kd_registration_fee' => $order->isKdRegistrationFee(),
                    'items_count' => $order->items->sum('quantity'),
                ];
            })->values()->all();
        }

        return $data;
    }
}
