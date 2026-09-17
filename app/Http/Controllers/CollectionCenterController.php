<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BranchStock;
use App\Models\CollectionCenterMove;
use App\Models\Order;
use App\Models\OrderGroup;
use App\Models\PosMachine;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CollectionCenterController extends Controller
{
    public function index()
    {
        $branches = $this->branchesQuery()->orderBy('name')->get();

        return view('collection-centers.index', [
            'branches' => $branches,
            'selectedId' => (int) session('collection_branch_id', 0),
        ]);
    }

    public function show(User $branch)
    {
        $this->ensureBranch($branch);
        $branch->load('role');

        return view('collection-centers.show', [
            'branch' => $branch,
            'banks' => Bank::where('branch_user_id', $branch->id)->where('is_active', true)->orderBy('name')->get(),
            'posMachines' => PosMachine::where('branch_user_id', $branch->id)->where('is_active', true)->orderBy('bank_name')->get(),
            'canManage' => $this->canManageBranch(request()->user(), $branch),
            'selected' => (int) session('collection_branch_id', 0) === (int) $branch->id,
        ]);
    }

    public function select(User $branch)
    {
        $this->ensureBranch($branch);
        session(['collection_branch_id' => $branch->id]);

        return redirect()->route('checkout.show')->with('success', 'Collection center set to '.$branch->name.'.');
    }

    public function clear()
    {
        session()->forget('collection_branch_id');

        return redirect()->route('checkout.show')->with('message', 'Collection center cleared.');
    }

    public function storeAccount(Request $request, User $branch)
    {
        $this->ensureBranch($branch);
        abort_unless($this->canManageBranch($request->user(), $branch), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
        ]);

        Bank::create([
            'name' => $data['name'],
            'account_name' => $data['account_name'],
            'account_number' => $data['account_number'],
            'is_active' => true,
            'branch_user_id' => $branch->id,
            'headquarters_user_id' => $branch->created_by_user_id,
        ]);

        return back()->with('success', 'Account added for this branch.');
    }

    public function storeProof(Request $request, User $branch)
    {
        $this->ensureBranch($branch);
        $request->validate([
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        if ($request->hasFile('payment_proof')) {
            $path = $request->file('payment_proof')->store('collection-proofs', 'public');
            $request->session()->put('collection_payment_proof', $path);
        }

        $request->session()->put('collection_branch_id', $branch->id);

        return back()->with('success', 'Payment proof saved. It will be attached when you place the order.');
    }

    public function storePos(Request $request, User $branch)
    {
        $this->ensureBranch($branch);
        abort_unless($this->canManageBranch($request->user(), $branch), 403);

        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255', 'unique:pos_machines,account_number'],
        ]);

        PosMachine::create([
            'bank_name' => $data['bank_name'],
            'account_name' => $data['account_name'],
            'account_number' => $data['account_number'],
            'is_active' => true,
            'branch_user_id' => $branch->id,
        ]);

        return back()->with('success', 'POS added for this branch.');
    }

    public function incoming(Request $request)
    {
        $branch = $this->viewerBranch($request->user());
        abort_unless($branch, 403, 'Only a branch can see orders sent for collection.');

        $orders = Order::with(['user', 'items'])
            ->where('collection_branch_id', $branch->id)
            ->whereNull('collected_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('collection-centers.orders', [
            'orders' => $orders,
            'title' => 'Orders sent to '.$branch->name,
            'branch' => $branch,
            'canCollect' => true,
            'collectionBranches' => $this->branchesQuery()
                ->where('id', '!=', $branch->id)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'email']),
        ]);
    }

    public function invoice(Request $request, Order $order)
    {
        $this->authorizeCollectionOrder($request, $order);
        $order->load('user', 'items', 'collectionBranch');

        return view('collection-centers.invoice', ['order' => $order]);
    }

    public function proof(Request $request, Order $order)
    {
        $this->authorizeCollectionOrder($request, $order);

        return $this->proofResponse((string) $order->payment_proof);
    }

    public function sessionProof(Request $request)
    {
        return $this->proofResponse((string) $request->session()->get('collection_payment_proof'));
    }

    public function collect(Request $request, Order $order)
    {
        $branch = $this->viewerBranch($request->user());
        abort_unless($branch && (int) $order->collection_branch_id === (int) $branch->id, 403);
        if ($order->collected_at) {
            return back()->with('error', 'This order is already collected.');
        }

        $request->validate([
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $order->load('items');
        foreach ($order->items as $item) {
            $product = Product::where('item_code', $item->item_code)->first();
            if (! $product) {
                return back()->with('error', 'Product not found for '.$item->product_name.'.');
            }
            $available = BranchStock::getQuantity($branch->id, $product->id);
            if ($available < (int) $item->quantity) {
                return back()->with(
                    'error',
                    "Not enough branch stock for {$item->product_name}. Available: {$available}. Use “Move to another collection center” below to send this order somewhere that can fulfill it."
                );
            }
        }

        try {
            DB::transaction(function () use ($order, $branch, $request) {
                foreach ($order->items as $item) {
                    $product = Product::where('item_code', $item->item_code)->lockForUpdate()->first();
                    if (! $product || ! BranchStock::decrementStock($branch->id, $product->id, (int) $item->quantity)) {
                        throw new \RuntimeException('Could not deduct branch stock for '.$item->product_name.'. Use “Move to another collection center” if this branch cannot fulfill the order.');
                    }
                }

                $proofPath = $order->payment_proof;
                if ($request->hasFile('payment_proof')) {
                    $proofPath = $request->file('payment_proof')->store('collection-proofs', 'public');
                }

                $order->update([
                    'collected_at' => now(),
                    'collected_by_user_id' => $request->user()->id,
                    'stock_deducted_at' => $order->stock_deducted_at ?? now(),
                    'payment_proof' => $proofPath,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Collected. Stock removed from '.$branch->name.'.');
    }

    /**
     * Reassign an uncollected order to a different collection center (branch).
     */
    public function moveOrder(Request $request, Order $order)
    {
        $branch = $this->viewerBranch($request->user());
        $user = $request->user();
        $user->loadMissing('role');
        $seeAll = $user->isSuperAdmin() || $user->role?->name === Role::HEADQUARTERS;

        abort_unless(
            $order->collection_branch_id
            && ! $order->collected_at
            && ($seeAll || ($branch && (int) $order->collection_branch_id === (int) $branch->id)),
            403
        );

        $data = $request->validate([
            'collection_branch_id' => ['required', 'integer'],
        ]);

        $destination = User::where('id', $data['collection_branch_id'])
            ->whereHas('role', fn ($q) => $q->where('name', Role::BRANCH))
            ->first();

        if (! $destination) {
            return back()->withErrors(['collection_branch_id' => 'Choose a valid collection center.'])->withInput();
        }

        if ((int) $destination->id === (int) $order->collection_branch_id) {
            return back()->with('message', 'Order is already at '.$destination->name.'.');
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
                'reason' => 'Moved from collection center (could not fulfill)',
            ]);

            if ($order->order_group_id) {
                OrderGroup::where('id', $order->order_group_id)
                    ->update(['collection_branch_id' => $destination->id]);
            }
        });

        return redirect()
            ->route('collection-centers.incoming')
            ->with('success', 'Order '.($order->invoice_number ?: '#'.$order->id).' moved from '.$fromName.' to '.$destination->name.'.');
    }

    public function collected(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');
        $role = $user->role?->name;
        $branch = $this->viewerBranch($user);
        $seeAll = $user->isSuperAdmin() || $role === Role::HEADQUARTERS;

        abort_unless($seeAll || $branch, 403);

        $query = Order::with(['user', 'items', 'collectionBranch'])
            ->whereNotNull('collected_at')
            ->orderByDesc('collected_at');

        if (! $seeAll) {
            $query->where('collection_branch_id', $branch->id);
        }

        return view('collection-centers.orders', [
            'orders' => $query->paginate(20),
            'title' => $seeAll ? 'All collected orders' : 'Collected at '.$branch->name,
            'branch' => $branch,
            'canCollect' => false,
        ]);
    }

    private function authorizeCollectionOrder(Request $request, Order $order): void
    {
        $user = $request->user();
        $user->loadMissing('role');
        $branch = $this->viewerBranch($user);
        $seeAll = $user->isSuperAdmin() || $user->role?->name === Role::HEADQUARTERS;
        $relatedIds = array_filter([
            (int) $order->collection_branch_id,
            (int) $order->branch_user_id,
            (int) $order->user_id,
        ]);
        $ownsOrder = in_array((int) $user->id, $relatedIds, true)
            || ($branch && in_array((int) $branch->id, $relatedIds, true));

        abort_unless($order->collection_branch_id && ($seeAll || $ownsOrder), 403);
    }

    private function proofResponse(string $path)
    {
        abort_unless($path !== '' && ! str_contains($path, '..') && str_starts_with($path, 'collection-proofs/'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    private function branchesQuery()
    {
        return User::whereHas('role', function ($q) {
            $q->where('name', Role::BRANCH);
        });
    }

    private function ensureBranch(User $branch): void
    {
        $branch->loadMissing('role');
        abort_unless($branch->role?->name === Role::BRANCH, 404);
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
        if (in_array($user->role?->name, [Role::CASHIER, Role::ACCOUNTANT, Role::DISPATCH], true) && $user->createdBy?->role?->name === Role::BRANCH) {
            return $user->createdBy;
        }

        return null;
    }

    private function canManageBranch(?User $user, User $branch): bool
    {
        if (! $user) {
            return false;
        }
        $user->loadMissing('role');
        if ($user->isSuperAdmin() || $user->role?->name === Role::HEADQUARTERS) {
            return true;
        }
        $viewerBranch = $this->viewerBranch($user);

        return $viewerBranch && (int) $viewerBranch->id === (int) $branch->id;
    }
}
