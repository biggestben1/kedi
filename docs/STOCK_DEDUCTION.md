# Stock Deduction Implementation

## Overview
Stock is automatically deducted from the `products` table when orders are **completed** (marked as delivered). For branch/annex users, stock is also deducted from `branch_stocks`.

**Important:** Stock is NOT deducted when orders are placed - only when they are completed/delivered.

## Order Placement Points

### ✅ 1. Web Checkout - Regular Order (`CheckoutController::placeOrder`)
- **File**: `app/Http/Controllers/CheckoutController.php` (line ~130)
- **Stock Check**: ✅ Validates stock before order placement
  - Regular users: Checks `products.stock`
  - Annex users: Checks `branch_stocks` quantity
- **Stock Deduction**: ❌ **No deduction** (stock deducted when order is completed)

### ✅ 2. Web Checkout - Save Draft (`CheckoutController::saveToDraft`)
- **File**: `app/Http/Controllers/CheckoutController.php` (line ~192)
- **Stock Check**: ✅ Validates stock before saving draft
- **Stock Deduction**: ❌ **No deduction** (correct - drafts don't deduct until placed)

### ✅ 3. Web Checkout - Place Draft from Wallet (`CheckoutController::placeDraftFromWallet`)
- **File**: `app/Http/Controllers/CheckoutController.php` (line ~286)
- **Stock Check**: ✅ Validates stock before placing draft
  - Regular users: Checks `products.stock`
  - Annex users: Checks `branch_stocks` quantity
- **Stock Deduction**: ❌ **No deduction** (stock deducted when order is completed)

### ✅ 4. Web Checkout - Place All Drafts from Wallet (`CheckoutController::placeAllDraftsFromWallet`)
- **File**: `app/Http/Controllers/CheckoutController.php` (line ~369)
- **Stock Check**: ✅ Validates stock for all drafts before placing
  - Regular users: Checks `products.stock`
  - Annex users: Checks `branch_stocks` quantity
- **Stock Deduction**: ❌ **No deduction** (stock deducted when order is completed)

### ✅ 5. Mobile API - Place Order (`Api\OrderController::store`)
- **File**: `app/Http/Controllers/Api/OrderController.php` (line ~42)
- **Stock Check**: ✅ Validates stock before order placement
  - Regular users: Checks `products.stock`
  - Annex users: Checks `branch_stocks` quantity
- **Stock Deduction**: ❌ **No deduction** (stock deducted when order is completed)

### ✅ 6. Dispatch - Mark Order as Delivered/Completed (`DispatchOrderController::updateStatus`)
- **File**: `app/Http/Controllers/DispatchOrderController.php` (line ~73)
- **Stock Deduction**: ✅ **Deducts stock when order status is set to "delivered" (which sets status to "completed")**
- **Branch Stock**: ✅ Also deducts from `branch_stocks` if order has branch_user_id
- **Prevents Duplicates**: ✅ Checks if order was already completed to prevent duplicate deduction

### ✅ 7. Mobile API - Driver Update Status (`Api\DriverOrderController::updateStatus`)
- **File**: `app/Http/Controllers/Api/DriverOrderController.php` (line ~76)
- **Stock Deduction**: ✅ **Deducts stock when order status is set to "delivered" (which sets status to "completed")**
- **Branch Stock**: ✅ Also deducts from `branch_stocks` if order has branch_user_id
- **Prevents Duplicates**: ✅ Checks if order was already completed to prevent duplicate deduction

### ⚠️ 6. Admin - Invoice to Order (`SuperAdminInvoiceController::moveToDispatch`)
- **File**: `app/Http/Controllers/SuperAdminInvoiceController.php` (line ~483)
- **Stock Check**: ❌ No stock validation
- **Stock Deduction**: ❌ **No deduction** (invoices may contain custom items not mapped to products)

## Stock Deduction Logic

**When order is marked as completed (delivered):**

```php
// For each order item:
// 1. Skip invoice items (they don't map to products)
if (str_starts_with($item->item_code, 'INV-')) {
    continue;
}

// 2. Find product by item_code
$product = Product::where('item_code', $item->item_code)->first();

// 3. Deduct from main product stock
$product->decrement('stock', $quantity);

// 4. Also deduct from branch stock (if branch order)
if ($branchUserId) {
    BranchStock::decrementStock($branchUserId, $product->id, $quantity);
}
```

## Stock Validation Logic

```php
// Before order placement:
if ($branchUserId) {
    // Annex users: Check branch stock
    $avail = BranchStock::getQuantity($branchUserId, $product->id);
    if ($avail < $quantity) {
        return error("Insufficient branch stock");
    }
} else {
    // Regular users: Check main product stock
    if ($product->stock < $quantity) {
        return error("Insufficient stock");
    }
}
```

## Summary

✅ **Stock is deducted when orders are completed (marked as delivered):**
- Dispatch marks order as "delivered" → status changes to "completed" → stock deducted
- Mobile API driver marks order as "delivered" → status changes to "completed" → stock deducted
- Prevents duplicate deduction if order was already completed

✅ **Stock validation happens before order placement** to prevent overselling

✅ **Orders don't deduct stock when placed** - only when completed/delivered

✅ **Draft orders don't deduct stock** until they're completed (correct behavior)

⚠️ **Invoice-to-order conversion** doesn't deduct stock immediately (stock deducted when order is completed)
