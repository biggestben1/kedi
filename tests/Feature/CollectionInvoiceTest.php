<?php

use App\Models\BranchStock;
use App\Models\CollectionInvoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function userWithRole(string $role): User
{
    $roleModel = Role::firstOrCreate(['name' => $role], ['display_name' => ucfirst($role)]);

    return User::factory()->create(['role_id' => $roleModel->id]);
}

beforeEach(function () {
    $this->branch = userWithRole(Role::BRANCH);
    $this->product = Product::create([
        'item_code' => 'P-001',
        'name' => 'Vitamin C',
        'pack_size' => '30 tabs',
        'price' => 1000,
        'bv' => 2,
        'pv' => 3,
        'stock' => 0,
        'is_active' => true,
    ]);
});

it('creates an invoice without checking stock and pushes it to the collection center', function () {
    $creator = userWithRole(Role::HEADQUARTERS);

    $this->actingAs($creator)
        ->post(route('collection-invoices.store'), [
            'customer_name' => 'Ada Buyer',
            'customer_phone' => '08000000000',
            'collection_branch_id' => $this->branch->id,
            'items' => [
                ['item_name' => 'Vitamin C (30 tabs)', 'quantity' => 5, 'unit_price' => 1000],
                ['item_name' => 'Delivery fee', 'quantity' => 1, 'unit_price' => 500],
                ['item_name' => '', 'quantity' => 1, 'unit_price' => ''],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $invoice = CollectionInvoice::with('items', 'order.items')->sole();
    expect($invoice->invoice_number)->toBe('CINV-000001')
        ->and((float) $invoice->subtotal)->toBe(5500.0)
        ->and($invoice->items->first()->product_id)->toBe($this->product->id)
        ->and($invoice->items->last()->product_id)->toBeNull();

    $order = $invoice->order;
    expect($order->collection_branch_id)->toBe($this->branch->id)
        ->and($order->collected_at)->toBeNull()
        ->and($order->status)->toBe(Order::STATUS_PENDING)
        ->and($order->items->pluck('item_code')->all())->toBe(['P-001', 'CINV-1-2']);

    expect(BranchStock::getQuantity($this->branch->id, $this->product->id))->toBe(0);
});

it('deducts branch stock only for product lines when the center collects', function () {
    BranchStock::setQuantity($this->branch->id, $this->product->id, 10);

    $this->actingAs(userWithRole(Role::RESELLER))
        ->post(route('collection-invoices.store'), [
            'customer_name' => 'Ada Buyer',
            'collection_branch_id' => $this->branch->id,
            'items' => [
                ['item_name' => 'vitamin c', 'quantity' => 4, 'unit_price' => 1000],
                ['item_name' => 'Delivery fee', 'quantity' => 1, 'unit_price' => 500],
            ],
        ])
        ->assertSessionHasNoErrors();

    $order = CollectionInvoice::sole()->order;

    $this->actingAs($this->branch)
        ->post(route('collection-centers.collect', $order))
        ->assertSessionHas('success');

    expect(BranchStock::getQuantity($this->branch->id, $this->product->id))->toBe(6)
        ->and($order->fresh()->collected_at)->not->toBeNull()
        ->and(CollectionInvoice::sole()->isCollected())->toBeTrue();
});

it('blocks collection when the center does not have enough stock', function () {
    BranchStock::setQuantity($this->branch->id, $this->product->id, 1);

    $this->actingAs(userWithRole(Role::CUSTOMER))
        ->post(route('collection-invoices.store'), [
            'customer_name' => 'Ada Buyer',
            'collection_branch_id' => $this->branch->id,
            'items' => [['item_name' => 'P-001', 'quantity' => 3, 'unit_price' => 1000]],
        ])
        ->assertSessionHasNoErrors();

    $order = CollectionInvoice::sole()->order;

    $this->actingAs($this->branch)
        ->post(route('collection-centers.collect', $order))
        ->assertSessionHas('error');

    expect(BranchStock::getQuantity($this->branch->id, $this->product->id))->toBe(1)
        ->and($order->fresh()->collected_at)->toBeNull();
});

it('rejects a collection center that is not a branch', function () {
    $notABranch = userWithRole(Role::CUSTOMER);

    $this->actingAs(userWithRole(Role::SUPER_ADMIN))
        ->post(route('collection-invoices.store'), [
            'customer_name' => 'Ada Buyer',
            'collection_branch_id' => $notABranch->id,
            'items' => [['item_name' => 'Vitamin C', 'quantity' => 1, 'unit_price' => 1000]],
        ])
        ->assertSessionHasErrors('collection_branch_id');

    expect(CollectionInvoice::count())->toBe(0);
});

it('deletes an uncollected invoice and removes it from the center', function () {
    $creator = userWithRole(Role::ACCOUNTANT);

    $this->actingAs($creator)->post(route('collection-invoices.store'), [
        'customer_name' => 'Ada Buyer',
        'collection_branch_id' => $this->branch->id,
        'items' => [['item_name' => 'Vitamin C', 'quantity' => 1, 'unit_price' => 1000]],
    ]);

    $invoice = CollectionInvoice::sole();
    $orderId = $invoice->order_id;

    $this->actingAs($creator)
        ->delete(route('collection-invoices.destroy', $invoice))
        ->assertRedirect(route('collection-invoices.index'));

    expect(CollectionInvoice::count())->toBe(0)
        ->and(Order::find($orderId))->toBeNull();
});

it('shows invoices on the branch invoices page, separate from normal orders', function () {
    BranchStock::setQuantity($this->branch->id, $this->product->id, 10);

    $normalOrder = Order::create([
        'user_id' => userWithRole(Role::CUSTOMER)->id,
        'collection_branch_id' => $this->branch->id,
        'invoice_number' => 'ORD-NORMAL-1',
        'subtotal' => 1000,
        'total_bv' => 0,
        'total_pv' => 0,
        'payment_method' => Order::PAYMENT_WALLET,
        'status' => Order::STATUS_PAID,
        'delivery_type' => Order::DELIVERY_WALK_IN,
        'shipping_address' => 'Walk-in (Pick up)',
    ]);

    $this->actingAs(userWithRole(Role::RESELLER))->post(route('collection-invoices.store'), [
        'customer_name' => 'Invoice Buyer',
        'collection_branch_id' => $this->branch->id,
        'items' => [['item_name' => 'Vitamin C', 'quantity' => 2, 'unit_price' => 1000]],
    ]);
    $invoice = CollectionInvoice::sole();

    $this->actingAs($this->branch)->get(route('collection-centers.incoming'))
        ->assertOk()
        ->assertSee('ORD-NORMAL-1')
        ->assertDontSee('Invoice Buyer');

    $this->actingAs($this->branch)->get(route('collection-centers.invoices'))
        ->assertOk()
        ->assertSee($invoice->invoice_number)
        ->assertSee('Invoice Buyer')
        ->assertDontSee('ORD-NORMAL-1');

    $this->actingAs($this->branch)->post(route('collection-centers.collect', $invoice->order))
        ->assertSessionHas('success');

    $this->actingAs($this->branch)->get(route('collection-centers.invoices'))
        ->assertOk()
        ->assertDontSee('Invoice Buyer');

    $this->actingAs($this->branch)->get(route('collection-centers.invoices', ['status' => 'collected']))
        ->assertOk()
        ->assertSee('Invoice Buyer');

    $this->actingAs($this->branch)->get(route('collection-centers.collected'))
        ->assertOk()
        ->assertDontSee('Invoice Buyer');

    expect($normalOrder->fresh()->collected_at)->toBeNull();
});

it('does not let a non-branch user open the branch invoices page', function () {
    $this->actingAs(userWithRole(Role::CUSTOMER))
        ->get(route('collection-centers.invoices'))
        ->assertForbidden();
});

it('renders the invoice pages for admin and customer roles', function (string $role) {
    $user = userWithRole($role);

    $this->actingAs($user)->get(route('collection-invoices.index'))->assertOk()->assertSee('New Invoice');
    $this->actingAs($user)->get(route('collection-invoices.create'))->assertOk()->assertSee($this->branch->name);

    $this->actingAs($user)->post(route('collection-invoices.store'), [
        'customer_name' => 'Ada Buyer',
        'collection_branch_id' => $this->branch->id,
        'items' => [['item_name' => 'Vitamin C', 'quantity' => 1, 'unit_price' => 1000]],
    ]);

    $this->actingAs($user)->get(route('collection-invoices.show', CollectionInvoice::sole()))
        ->assertOk()
        ->assertSee('Waiting for collection');
})->with([
    Role::SUPER_ADMIN, Role::ACCOUNTANT, Role::HEADQUARTERS, Role::BRANCH,
    Role::SERVICE_CENTER, Role::ANNEX, Role::RESELLER, Role::CUSTOMER,
]);
