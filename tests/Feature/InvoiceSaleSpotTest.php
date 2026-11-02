<?php

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderGroup;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows kedi number, add to group, and collection center on the invoice form', function () {
    $role = Role::firstOrCreate(['name' => Role::BRANCH], ['display_name' => 'Branch']);
    $user = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($user)
        ->get(route('invoices.create'))
        ->assertOk()
        ->assertSee('Kedi number')
        ->assertSee('Add to group')
        ->assertSee('Collection center');
});

it('saves a draft invoice into the chosen group with the kedi number and collection center', function () {
    $sellerRole = Role::firstOrCreate(['name' => Role::RESELLER], ['display_name' => 'Reseller']);
    $branchRole = Role::firstOrCreate(['name' => Role::BRANCH], ['display_name' => 'Branch']);
    $seller = User::factory()->create(['role_id' => $sellerRole->id]);
    $branch = User::factory()->create(['role_id' => $branchRole->id, 'name' => 'Ikeja Center']);
    $group = OrderGroup::create([
        'user_id' => $seller->id,
        'name' => 'Counter group',
        'status' => OrderGroup::STATUS_OPEN,
    ]);
    Product::create([
        'item_code' => 'P-001',
        'name' => 'Vitamin C',
        'price' => 1000,
        'bv' => 1,
        'pv' => 1,
        'stock' => 5,
        'is_active' => true,
    ]);

    $this->actingAs($seller)
        ->post(route('invoices.store'), [
            'invoice_date' => now()->toDateString(),
            'status' => 'paid',
            'add_to_group' => 1,
            'kd_id' => 'kd-100',
            'order_group_id' => $group->id,
            'collection_branch_id' => $branch->id,
            'buyer_name' => 'Ada Buyer',
            'items' => [
                ['item_name' => 'Vitamin C', 'quantity' => 2, 'unit' => 'pcs', 'unit_price' => 1000],
            ],
        ])
        ->assertRedirect(route('order-groups.show', $group))
        ->assertSessionHasNoErrors();

    $invoice = Invoice::sole();
    expect($invoice->status)->toBe('draft')
        ->and($invoice->kd_id)->toBe('KD-100')
        ->and($invoice->order_group_id)->toBe($group->id)
        ->and($invoice->collection_branch_id)->toBe($branch->id)
        ->and($invoice->stock_deducted_at)->toBeNull();

    $order = Order::where('invoice_id', $invoice->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(Order::STATUS_DRAFT)
        ->and($order->kd_id)->toBe('KD-100')
        ->and($order->order_group_id)->toBe($group->id)
        ->and($order->collection_branch_id)->toBe($branch->id)
        ->and($order->items)->toHaveCount(1);
});
