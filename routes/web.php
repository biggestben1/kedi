<?php

use App\Http\Controllers\AdminContactController;
use App\Http\Middleware\RestrictAnnexAdmin;
use App\Http\Middleware\RestrictBranchAdmin;
use App\Http\Middleware\RestrictDispatchAdmin;
use App\Http\Middleware\RestrictHeadquartersAdmin;
use App\Http\Middleware\RestrictResellerAdmin;
use App\Http\Middleware\RestrictServiceCenterAdmin;
use App\Http\Middleware\RestrictWholesaleStaffAdmin;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KdInfoController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PharmacyDashboardController;
use App\Http\Controllers\PharmacyReportsController;
use App\Http\Controllers\SuperAdminCategoryController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SuperAdminCouponController;
use App\Http\Controllers\SuperAdminProductController;
use App\Http\Controllers\SuperAdminPurchaseController;
use App\Http\Controllers\SuperAdminSupplierController;
use App\Http\Controllers\SuperAdminUserController;
use App\Http\Controllers\SuperAdminWalletTopupController;
use App\Http\Controllers\SuperAdminBankController;
use App\Http\Controllers\BackOrderController;
use App\Http\Controllers\BranchStockController;
use App\Http\Controllers\SuperAdminInStockController;
use App\Http\Controllers\SuperAdminInvoiceController;
use App\Http\Controllers\SuperAdminRoleController;
use App\Http\Controllers\CustomerInvoiceController;
use App\Http\Controllers\AccountantWalletController;
use App\Http\Controllers\DispatchOrderController;
use App\Http\Controllers\DpbvController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Admin\DpbvCollectionController;
use App\Http\Controllers\Admin\PromoCollectionController;
use App\Http\Controllers\Admin\BonusCollectionController;
use App\Http\Controllers\Admin\KdCustomerController;
use App\Http\Controllers\Admin\KdRegistrationController;
use App\Http\Controllers\Admin\KediKitController;
use App\Http\Controllers\Admin\KediKitPurchaseController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\SuperAdminExpenditureController;
use App\Http\Controllers\Admin\AnnouncementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/storage-link', function () {
    Artisan::call('storage:link');
    return 'Storage linked successfully! <a href="' . url('/clear') . '">Clear cache</a>';
});

Route::get('/clear', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    return 'Cache cleared! <a href="' . url('/storage-link') . '">Create storage link</a>';
});

// Contact Us (public – guests and auth)
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// KD ID & Customer Name (session)
Route::post('/kd-info', [KdInfoController::class, 'store'])->name('kd-info.store')->middleware('auth');
Route::post('/kd-info/auto-generate', [KdInfoController::class, 'autoGenerate'])->name('kd-info.auto-generate')->middleware('auth');
Route::post('/kd-info/search', [KdInfoController::class, 'search'])->name('kd-info.search')->middleware('auth');

// Cart JSON endpoint for AJAX cart UI
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{item_code}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/apply-coupon', [CartController::class, 'applyCoupon'])->name('cart.apply-coupon');
Route::post('/cart/remove-coupon', [CartController::class, 'removeCoupon'])->name('cart.remove-coupon');

// Authentication Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
//    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
//    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.update.change');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Customer: Checkout & Wallet
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'placeOrder'])->name('checkout.place');
    Route::post('/checkout/check-kd-credit', [CheckoutController::class, 'checkKdCredit'])->name('checkout.check-kd-credit');
    Route::post('/checkout/validate-sc-code', [CheckoutController::class, 'validateScCode'])->name('checkout.validate-sc-code');
    Route::post('/checkout/dpbv', [CheckoutController::class, 'buyWithDpbv'])->name('checkout.dpbv');
    Route::post('/checkout/save-draft', [CheckoutController::class, 'saveToDraft'])->name('checkout.save-draft');
    Route::post('/orders/{order}/restore-draft', [CheckoutController::class, 'restoreDraft'])->name('orders.restore-draft');
    Route::post('/orders/{order}/place-draft-wallet', [CheckoutController::class, 'placeDraftFromWallet'])->name('orders.place-draft-wallet');
    Route::post('/orders/place-all-drafts-wallet', [CheckoutController::class, 'placeAllDraftsFromWallet'])->name('orders.place-all-drafts-wallet');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/top-up', [WalletController::class, 'topUp'])->name('wallet.top-up');

    // My Orders (view orders and tracking)
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/export/csv', [OrderController::class, 'exportCsv'])->name('orders.export.csv');
    Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
    Route::get('/orders/{order}/receipt/pdf', [OrderController::class, 'receiptPdf'])->name('orders.receipt.pdf');
    Route::post('/orders/receipt-pdf', [OrderController::class, 'receiptPdfSelected'])->name('orders.receipt.pdf.selected');
    Route::post('/orders/add-for-supply', [CartController::class, 'addFromSelectedOrders'])->name('orders.add-for-supply');
    Route::get('/orders/added-items/pdf', [OrderController::class, 'addedItemsPdf'])->name('orders.added-items.pdf');
    Route::post('/orders/added-items/clear', [OrderController::class, 'clearAddedItems'])->name('orders.added-items.clear');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // My DPBV (user view their DPBV records)
    Route::get('/dpbv', [DpbvController::class, 'index'])->name('dpbv.index');
    Route::get('/dpbv/spending', [DpbvController::class, 'spending'])->name('dpbv.spending');

    // My Promo (user view their promo records)
    Route::get('/promo', [PromoController::class, 'index'])->name('promo.index');

    // My Bonus (user view their bonus records)
    Route::get('/bonus', [BonusController::class, 'index'])->name('bonus.index');

    // My Invoices (customer view their invoices)
    Route::get('/my-invoices', [CustomerInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/my-invoices/create', [CustomerInvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/my-invoices', [CustomerInvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/my-invoices/{invoice}/pdf', [CustomerInvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Admin area (super_admin: full; wholesale_staff: dashboard, reports, users, invoices; reseller: users + invoices; accountant: wallet; dispatch: orders only)
    Route::middleware(['role:super_admin,wholesale_staff,reseller,accountant,dispatch,headquarters,branch,service_center,annex', RestrictWholesaleStaffAdmin::class, RestrictResellerAdmin::class, RestrictDispatchAdmin::class, RestrictHeadquartersAdmin::class, RestrictBranchAdmin::class, RestrictServiceCenterAdmin::class, RestrictAnnexAdmin::class])->group(function () {
        Route::get('/admin', function () {
            if (auth()->user()?->role?->name === 'reseller') {
                return redirect()->route('admin.users.index', ['role' => 'customer', 'created_by' => auth()->id()]);
            }
            if (auth()->user()?->role?->name === 'accountant') {
                return redirect()->route('admin.accountant.wallet.index');
            }
            if (auth()->user()?->role?->name === 'dispatch') {
                return redirect()->route('admin.dispatch.orders.index');
            }
            if (auth()->user()?->role?->name === 'headquarters') {
                return redirect()->route('admin.invoices.index');
            }
            if (auth()->user()?->role?->name === 'branch') {
                return redirect()->route('admin.pharmacy.dashboard');
            }
            if (auth()->user()?->role?->name === 'service_center') {
                return redirect()->route('admin.pharmacy.dashboard');
            }
            if (auth()->user()?->role?->name === 'annex') {
                return redirect()->route('admin.pharmacy.dashboard');
            }
            return redirect()->route('admin.pharmacy.dashboard');
        })->name('admin');

        // Dispatch – Orders only (view, update status, tracking, print)
        Route::get('/admin/dispatch/orders', [DispatchOrderController::class, 'index'])->name('admin.dispatch.orders.index');
        Route::get('/admin/dispatch/orders/{order}', [DispatchOrderController::class, 'show'])->name('admin.dispatch.orders.show');
        Route::get('/admin/dispatch/orders/{order}/view', [DispatchOrderController::class, 'viewOrder'])->name('admin.dispatch.orders.view');
        Route::post('/admin/dispatch/orders/{order}/status', [DispatchOrderController::class, 'updateStatus'])->name('admin.dispatch.orders.update-status');
        Route::post('/admin/dispatch/orders/{order}/tracking', [DispatchOrderController::class, 'updateTracking'])->name('admin.dispatch.orders.update-tracking');
        Route::post('/admin/dispatch/orders/{order}/shipping-cost', [DispatchOrderController::class, 'updateShippingCost'])->name('admin.dispatch.orders.update-shipping-cost');
        Route::post('/admin/dispatch/orders/{order}/items/{item}/exchange', [DispatchOrderController::class, 'exchangeItem'])->name('admin.dispatch.orders.exchange-item');
        Route::get('/admin/dispatch/orders/{order}/invoice', [DispatchOrderController::class, 'invoice'])->name('admin.dispatch.orders.invoice');
        Route::get('/admin/dispatch/orders/{order}/delivery-note', [DispatchOrderController::class, 'deliveryNote'])->name('admin.dispatch.orders.delivery-note');
        Route::get('/admin/dispatch/orders/{order}/shipment-label', [DispatchOrderController::class, 'shipmentLabel'])->name('admin.dispatch.orders.shipment-label');

        // Pharmacy Dashboard & Reports
        Route::get('/admin/pharmacy', [PharmacyDashboardController::class, 'index'])->name('admin.pharmacy.dashboard');
        Route::get('/admin/pharmacy/reports', [PharmacyReportsController::class, 'index'])->name('admin.pharmacy.reports');
        Route::get('/admin/pharmacy/referred-orders', [PharmacyDashboardController::class, 'referredOrders'])->name('admin.pharmacy.referred-orders');
        Route::get('/admin/pharmacy/reports/export/pdf', [PharmacyReportsController::class, 'exportPdf'])->name('admin.pharmacy.reports.export.pdf');
        Route::get('/admin/pharmacy/reports/export/excel', [PharmacyReportsController::class, 'exportExcel'])->name('admin.pharmacy.reports.export.excel');

        // Users CRUD
        Route::get('/admin/users', [SuperAdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/create', [SuperAdminUserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users', [SuperAdminUserController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/users/{user}/edit', [SuperAdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/admin/users/{user}', [SuperAdminUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [SuperAdminUserController::class, 'destroy'])->name('admin.users.destroy');

        // Roles CRUD (Super Admin only)
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/admin/roles', [SuperAdminRoleController::class, 'index'])->name('admin.roles.index');
            Route::get('/admin/roles/create', [SuperAdminRoleController::class, 'create'])->name('admin.roles.create');
            Route::post('/admin/roles', [SuperAdminRoleController::class, 'store'])->name('admin.roles.store');
            Route::get('/admin/roles/{role}/edit', [SuperAdminRoleController::class, 'edit'])->name('admin.roles.edit');
            Route::put('/admin/roles/{role}', [SuperAdminRoleController::class, 'update'])->name('admin.roles.update');
            Route::delete('/admin/roles/{role}', [SuperAdminRoleController::class, 'destroy'])->name('admin.roles.destroy');

            // Coupons Management
            Route::resource('/admin/coupons', SuperAdminCouponController::class)->names([
                'index' => 'admin.coupons.index',
                'create' => 'admin.coupons.create',
                'store' => 'admin.coupons.store',
                'edit' => 'admin.coupons.edit',
                'update' => 'admin.coupons.update',
                'destroy' => 'admin.coupons.destroy',
            ]);

            // Announcements Management
            Route::get('/admin/announcements', [AnnouncementController::class, 'index'])->name('admin.announcements.index');
            Route::get('/admin/announcements/create', [AnnouncementController::class, 'create'])->name('admin.announcements.create');
            Route::post('/admin/announcements', [AnnouncementController::class, 'store'])->name('admin.announcements.store');
            Route::get('/admin/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('admin.announcements.edit');
            Route::put('/admin/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('admin.announcements.update');
            Route::delete('/admin/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');
            Route::patch('/admin/announcements/{announcement}/toggle-active', [AnnouncementController::class, 'toggleActive'])->name('admin.announcements.toggle-active');
        });

        // Categories CRUD
        Route::get('/admin/categories', [SuperAdminCategoryController::class, 'index'])->name('admin.categories.index');
        Route::get('/admin/categories/create', [SuperAdminCategoryController::class, 'create'])->name('admin.categories.create');
        Route::post('/admin/categories', [SuperAdminCategoryController::class, 'store'])->name('admin.categories.store');
        Route::get('/admin/categories/{category}/edit', [SuperAdminCategoryController::class, 'edit'])->name('admin.categories.edit');
        Route::put('/admin/categories/{category}', [SuperAdminCategoryController::class, 'update'])->name('admin.categories.update');
        Route::delete('/admin/categories/{category}', [SuperAdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');

        // Products CRUD
        Route::get('/admin/products', [SuperAdminProductController::class, 'index'])->name('admin.products.index');
        Route::get('/admin/products/create', [SuperAdminProductController::class, 'create'])->name('admin.products.create');
        Route::post('/admin/products', [SuperAdminProductController::class, 'store'])->name('admin.products.store');
        Route::get('/admin/products/{product}/edit', [SuperAdminProductController::class, 'edit'])->name('admin.products.edit');
        Route::put('/admin/products/{product}', [SuperAdminProductController::class, 'update'])->name('admin.products.update');
        Route::delete('/admin/products/{product}', [SuperAdminProductController::class, 'destroy'])->name('admin.products.destroy');

        // Suppliers
        Route::get('/admin/suppliers', [SuperAdminSupplierController::class, 'index'])->name('admin.suppliers.index');
        Route::get('/admin/suppliers/create', [SuperAdminSupplierController::class, 'create'])->name('admin.suppliers.create');
        Route::post('/admin/suppliers', [SuperAdminSupplierController::class, 'store'])->name('admin.suppliers.store');
        Route::get('/admin/suppliers/{supplier}/edit', [SuperAdminSupplierController::class, 'edit'])->name('admin.suppliers.edit');
        Route::put('/admin/suppliers/{supplier}', [SuperAdminSupplierController::class, 'update'])->name('admin.suppliers.update');

        // Banks CRUD (Super Admin, Wholesale Staff, Accountant, Headquarters)
        Route::middleware('role:super_admin,wholesale_staff,accountant,headquarters')->group(function () {
            Route::get('/admin/banks', [SuperAdminBankController::class, 'index'])->name('admin.banks.index');
            Route::get('/admin/banks/create', [SuperAdminBankController::class, 'create'])->name('admin.banks.create');
            Route::post('/admin/banks', [SuperAdminBankController::class, 'store'])->name('admin.banks.store');
            Route::get('/admin/banks/{bank}/edit', [SuperAdminBankController::class, 'edit'])->name('admin.banks.edit');
            Route::put('/admin/banks/{bank}', [SuperAdminBankController::class, 'update'])->name('admin.banks.update');
            Route::post('/admin/banks/{bank}/deactivate', [SuperAdminBankController::class, 'deactivate'])->name('admin.banks.deactivate');
            Route::post('/admin/banks/{bank}/activate', [SuperAdminBankController::class, 'activate'])->name('admin.banks.activate');
            Route::delete('/admin/banks/{bank}', [SuperAdminBankController::class, 'destroy'])->name('admin.banks.destroy');
        });

        // Invoices CRUD
        Route::get('/admin/invoices', [SuperAdminInvoiceController::class, 'index'])->name('admin.invoices.index');
        Route::get('/admin/invoices/create', [SuperAdminInvoiceController::class, 'create'])->name('admin.invoices.create');
        Route::get('/admin/invoices/{invoice}', [SuperAdminInvoiceController::class, 'show'])->name('admin.invoices.show');
        Route::post('/admin/invoices', [SuperAdminInvoiceController::class, 'store'])->name('admin.invoices.store');
        Route::get('/admin/invoices/{invoice}/edit', [SuperAdminInvoiceController::class, 'edit'])->name('admin.invoices.edit');
        Route::put('/admin/invoices/{invoice}', [SuperAdminInvoiceController::class, 'update'])->name('admin.invoices.update');
        Route::delete('/admin/invoices/{invoice}', [SuperAdminInvoiceController::class, 'destroy'])->name('admin.invoices.destroy');
        Route::get('/admin/invoices/{invoice}/pdf', [SuperAdminInvoiceController::class, 'pdf'])->name('admin.invoices.pdf');
        Route::post('/admin/invoices/{invoice}/move-to-dispatch', [SuperAdminInvoiceController::class, 'moveToDispatch'])->name('admin.invoices.move-to-dispatch');
        Route::post('/admin/invoices/{invoice}/approve', [SuperAdminInvoiceController::class, 'approve'])->name('admin.invoices.approve');

        // Back orders (Super Admin: all; HQ: their branches/annex/service center; Branch: own)
        Route::get('/admin/back-orders', [BackOrderController::class, 'index'])->name('admin.back_orders.index');

        // Branch Stock (Branch: view own; Super Admin: allocate to branches)
        Route::get('/admin/branch/stock', [BranchStockController::class, 'index'])->name('admin.branch.stock.index');
        Route::get('/admin/branch/stock/allocate', [BranchStockController::class, 'allocate'])->name('admin.branch.stock.allocate');
        Route::post('/admin/branch/stock/allocate', [BranchStockController::class, 'storeAllocate'])->name('admin.branch.stock.store-allocate');

        // Contact Us messages (super_admin, wholesale_staff, reseller)
        Route::get('/admin/contacts', [AdminContactController::class, 'index'])->name('admin.contacts.index');
        Route::get('/admin/contacts/{contact}', [AdminContactController::class, 'show'])->name('admin.contacts.show');

        // DPBV Collections - HQ & Super Admin only (upload Excel)
        Route::get('/admin/dpbv', [DpbvCollectionController::class, 'index'])->name('admin.dpbv.index');
        Route::get('/admin/dpbv/create', [DpbvCollectionController::class, 'create'])->name('admin.dpbv.create');
        Route::post('/admin/dpbv', [DpbvCollectionController::class, 'store'])->name('admin.dpbv.store');
        Route::post('/admin/dpbv/rematch', [DpbvCollectionController::class, 'rematch'])->name('admin.dpbv.rematch');

        // Promo Collections - HQ & Super Admin only
        Route::get('/admin/promo', [PromoCollectionController::class, 'index'])->name('admin.promo.index');
        Route::get('/admin/promo/create', [PromoCollectionController::class, 'create'])->name('admin.promo.create');
        Route::post('/admin/promo', [PromoCollectionController::class, 'store'])->name('admin.promo.store');
        Route::post('/admin/promo/rematch', [PromoCollectionController::class, 'rematch'])->name('admin.promo.rematch');

        // Bonus Collections - HQ, Super Admin & Accountant
        Route::get('/admin/bonus', [BonusCollectionController::class, 'index'])->name('admin.bonus.index');
        Route::get('/admin/bonus/create', [BonusCollectionController::class, 'create'])->name('admin.bonus.create');
        Route::post('/admin/bonus', [BonusCollectionController::class, 'store'])->name('admin.bonus.store');
        Route::post('/admin/bonus/rematch', [BonusCollectionController::class, 'rematch'])->name('admin.bonus.rematch');
        Route::post('/admin/bonus/{bonus}/toggle-disbursement', [BonusCollectionController::class, 'toggleDisbursement'])->name('admin.bonus.toggle-disbursement');
        Route::post('/admin/bonus/bulk-disburse', [BonusCollectionController::class, 'bulkDisburse'])->name('admin.bonus.bulk-disburse');
        Route::post('/admin/bonus/bulk-undisburse', [BonusCollectionController::class, 'bulkUndisburse'])->name('admin.bonus.bulk-undisburse');

        // Borrow (KD Numbers) - all auto-generated KD NO, My Orders & Share
        Route::get('/admin/kd', [KdCustomerController::class, 'index'])->name('admin.kd.index');
        Route::get('/admin/kd/share', [KdCustomerController::class, 'showShare'])->name('admin.kd.show-share');
        Route::get('/admin/kd/share/{kd}', [KdCustomerController::class, 'showShare'])->name('admin.kd.show-share-kd');
        Route::post('/admin/kd/share', [KdCustomerController::class, 'share'])->name('admin.kd.share');

        // KD Registration
        Route::get('/admin/kd/registration', [KdRegistrationController::class, 'index'])->name('admin.kd.registration.index');
        Route::get('/admin/kd/registration/create', [KdRegistrationController::class, 'create'])->name('admin.kd.registration.create');
        Route::post('/admin/kd/registration', [KdRegistrationController::class, 'store'])->name('admin.kd.registration.store');
        Route::get('/admin/kd/registration/{registration}', [KdRegistrationController::class, 'show'])->name('admin.kd.registration.show');
        Route::post('/admin/kd/registration/{registration}/add-credit', [KdRegistrationController::class, 'addCredit'])->name('admin.kd.registration.add-credit');
        Route::get('/admin/kd/registration/{registration}/edit', [KdRegistrationController::class, 'edit'])->name('admin.kd.registration.edit');
        Route::put('/admin/kd/registration/{registration}', [KdRegistrationController::class, 'update'])->name('admin.kd.registration.update');
        Route::delete('/admin/kd/registration/{registration}', [KdRegistrationController::class, 'destroy'])->name('admin.kd.registration.destroy');

        // KEDI Kit Seller Dashboard - Super Admin, Headquarters, Branch, Service Center (sellers) - MUST BE BEFORE purchase/{purchase}
        Route::middleware('role:super_admin,headquarters,branch,service_center')->group(function () {
            Route::get('/admin/kedi-kits/purchase/seller', [KediKitPurchaseController::class, 'sellerDashboard'])->name('admin.kedi-kits.purchase.seller');
            Route::post('/admin/kedi-kits/back-order/{backOrder}/fulfill', [KediKitPurchaseController::class, 'fulfillBackOrder'])->name('admin.kedi-kits.back-order.fulfill');
        });

        // KEDI Kit Purchases - Headquarters, Branch, Service Center, Annex, Reseller, Customer
        Route::middleware('role:headquarters,branch,service_center,annex,reseller,customer')->group(function () {
            Route::get('/admin/kedi-kits/purchase', [KediKitPurchaseController::class, 'index'])->name('admin.kedi-kits.purchase.index');
            Route::get('/admin/kedi-kits/purchase/create', [KediKitPurchaseController::class, 'create'])->name('admin.kedi-kits.purchase.create');
            Route::post('/admin/kedi-kits/purchase', [KediKitPurchaseController::class, 'store'])->name('admin.kedi-kits.purchase.store');
            Route::get('/admin/kedi-kits/purchase/{purchase}', [KediKitPurchaseController::class, 'show'])->name('admin.kedi-kits.purchase.show');
            Route::post('/admin/kedi-kits/purchase/{purchase}/approve', [KediKitPurchaseController::class, 'approve'])->name('admin.kedi-kits.purchase.approve');
            Route::post('/admin/kedi-kits/purchase/{purchase}/reject', [KediKitPurchaseController::class, 'reject'])->name('admin.kedi-kits.purchase.reject');
            Route::post('/admin/kedi-kits/purchase/{purchase}/unassign-kd-numbers', [KediKitPurchaseController::class, 'unassignKdNumbers'])->name('admin.kedi-kits.purchase.unassign-kd-numbers');
            Route::post('/admin/kedi-kits/purchase/{purchase}/sync-registrations', [KediKitPurchaseController::class, 'syncRegistrations'])->name('admin.kedi-kits.purchase.sync-registrations');
        });

        // KEDI Kits - Super Admin only
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/admin/kedi-kits', [KediKitController::class, 'index'])->name('admin.kedi-kits.index');
            Route::get('/admin/kedi-kits/create', [KediKitController::class, 'create'])->name('admin.kedi-kits.create');
            Route::post('/admin/kedi-kits', [KediKitController::class, 'store'])->name('admin.kedi-kits.store');
            Route::get('/admin/kedi-kits/{kediKit}', [KediKitController::class, 'show'])->name('admin.kedi-kits.show');
            Route::get('/admin/kedi-kits/{kediKit}/edit', [KediKitController::class, 'edit'])->name('admin.kedi-kits.edit');
            Route::put('/admin/kedi-kits/{kediKit}', [KediKitController::class, 'update'])->name('admin.kedi-kits.update');
            Route::post('/admin/kedi-kits/{kediKit}/update-status', [KediKitController::class, 'updateStatus'])->name('admin.kedi-kits.update-status');
            Route::post('/admin/kedi-kits/{kediKit}/add-kd-numbers', [KediKitController::class, 'addKdNumbers'])->name('admin.kedi-kits.add-kd-numbers');
            Route::delete('/admin/kedi-kits/{kediKit}', [KediKitController::class, 'destroy'])->name('admin.kedi-kits.destroy');
        });

        // In Stock (Factory Invoices) - Super Admin only
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/admin/in-stock', [SuperAdminInStockController::class, 'index'])->name('admin.in-stock.index');
            Route::get('/admin/in-stock/create', [SuperAdminInStockController::class, 'create'])->name('admin.in-stock.create');
            Route::post('/admin/in-stock', [SuperAdminInStockController::class, 'store'])->name('admin.in-stock.store');
            Route::get('/admin/in-stock/{inStock}', [SuperAdminInStockController::class, 'show'])->name('admin.in-stock.show');
            Route::get('/admin/in-stock/{inStock}/edit', [SuperAdminInStockController::class, 'edit'])->name('admin.in-stock.edit');
            Route::put('/admin/in-stock/{inStock}', [SuperAdminInStockController::class, 'update'])->name('admin.in-stock.update');
            Route::delete('/admin/in-stock/{inStock}', [SuperAdminInStockController::class, 'destroy'])->name('admin.in-stock.destroy');
            Route::post('/admin/in-stock/{inStock}/add-to-stock', [SuperAdminInStockController::class, 'addToStock'])->name('admin.in-stock.add-to-stock');
            Route::post('/admin/in-stock/{inStock}/update-brought', [SuperAdminInStockController::class, 'updateBrought'])->name('admin.in-stock.update-brought');
            Route::get('/admin/in-stock/{inStock}/pdf', [SuperAdminInStockController::class, 'pdf'])->name('admin.in-stock.pdf');
        });

        // Expenditures (Super Admin only)
        Route::middleware('role:super_admin')->group(function () {
            Route::resource('/admin/expenditures', SuperAdminExpenditureController::class)->names([
                'index' => 'admin.expenditures.index',
                'create' => 'admin.expenditures.create',
                'store' => 'admin.expenditures.store',
                'show' => 'admin.expenditures.show',
                'edit' => 'admin.expenditures.edit',
                'update' => 'admin.expenditures.update',
                'destroy' => 'admin.expenditures.destroy',
            ]);
        });

        // Purchases (Purchase Invoices)
        Route::get('/admin/purchases', [SuperAdminPurchaseController::class, 'index'])->name('admin.purchases.index');
        Route::get('/admin/purchases/create', [SuperAdminPurchaseController::class, 'create'])->name('admin.purchases.create');
        Route::post('/admin/purchases', [SuperAdminPurchaseController::class, 'store'])->name('admin.purchases.store');
        Route::get('/admin/purchases/{purchase}/edit', [SuperAdminPurchaseController::class, 'edit'])->name('admin.purchases.edit');
        Route::put('/admin/purchases/{purchase}', [SuperAdminPurchaseController::class, 'update'])->name('admin.purchases.update');
        Route::delete('/admin/purchases/{purchase}', [SuperAdminPurchaseController::class, 'destroy'])->name('admin.purchases.destroy');
    });

    // Accountant, Super Admin, Headquarters, Branch, and Service Center - Banks, Wallet Management & Approvals
    Route::middleware('role:accountant,super_admin,headquarters,branch,service_center')->group(function () {
        // Banks CRUD
        Route::get('/admin/banks', [SuperAdminBankController::class, 'index'])->name('admin.banks.index');
        Route::get('/admin/banks/create', [SuperAdminBankController::class, 'create'])->name('admin.banks.create');
        Route::post('/admin/banks', [SuperAdminBankController::class, 'store'])->name('admin.banks.store');
        Route::get('/admin/banks/{bank}/edit', [SuperAdminBankController::class, 'edit'])->name('admin.banks.edit');
        Route::put('/admin/banks/{bank}', [SuperAdminBankController::class, 'update'])->name('admin.banks.update');
        Route::post('/admin/banks/{bank}/deactivate', [SuperAdminBankController::class, 'deactivate'])->name('admin.banks.deactivate');
        Route::post('/admin/banks/{bank}/activate', [SuperAdminBankController::class, 'activate'])->name('admin.banks.activate');
        Route::delete('/admin/banks/{bank}', [SuperAdminBankController::class, 'destroy'])->name('admin.banks.destroy');

        // Wallet Top-ups Approvals
        Route::get('/admin/wallet-topups', [SuperAdminWalletTopupController::class, 'index'])->name('admin.wallet_topups');
        Route::get('/admin/wallet-topups/approved', [SuperAdminWalletTopupController::class, 'approved'])->name('admin.wallet_topups.approved');
        Route::get('/admin/wallet-topups/rejected', [SuperAdminWalletTopupController::class, 'rejected'])->name('admin.wallet_topups.rejected');
        Route::post('/admin/wallet-topups/{tx}/approve', [SuperAdminWalletTopupController::class, 'approve'])->name('admin.wallet_topups.approve');
        Route::post('/admin/wallet-topups/{tx}/reject', [SuperAdminWalletTopupController::class, 'reject'])->name('admin.wallet_topups.reject');

        // Wallet Management
        Route::get('/admin/wallet-management', [AccountantWalletController::class, 'index'])->name('admin.accountant.wallet.index');
        Route::get('/admin/wallet-management/users', [AccountantWalletController::class, 'users'])->name('admin.accountant.wallet.users');
        Route::get('/admin/wallet-management/users/{user}/transactions', [AccountantWalletController::class, 'userTransactions'])->name('admin.accountant.wallet.user-transactions');
    });
});
