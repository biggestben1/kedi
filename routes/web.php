<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PharmacyDashboardController;
use App\Http\Controllers\PharmacyReportsController;
use App\Http\Controllers\SuperAdminCategoryController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SuperAdminProductController;
use App\Http\Controllers\SuperAdminPurchaseController;
use App\Http\Controllers\SuperAdminSupplierController;
use App\Http\Controllers\SuperAdminUserController;
use App\Http\Controllers\SuperAdminWalletTopupController;
use App\Http\Controllers\SuperAdminBankController;
use App\Http\Controllers\SuperAdminInvoiceController;
use App\Http\Controllers\CustomerInvoiceController;
use App\Http\Controllers\AccountantWalletController;
use App\Http\Controllers\DispatchOrderController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Cart JSON endpoint for AJAX cart UI
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{item_code}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Customer: Checkout & Wallet
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'placeOrder'])->name('checkout.place');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/top-up', [WalletController::class, 'topUp'])->name('wallet.top-up');

    // My Orders (view orders and tracking)
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // My Invoices (customer view their invoices)
    Route::get('/my-invoices', [CustomerInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/my-invoices/create', [CustomerInvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/my-invoices', [CustomerInvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/my-invoices/{invoice}/pdf', [CustomerInvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Admin area (super_admin: full; wholesale_staff: dashboard, reports, users, invoices; reseller: users + invoices; accountant: wallet; dispatch: orders only)
    Route::middleware(['role:super_admin,wholesale_staff,reseller,accountant,dispatch', 'restrict.wholesale_staff_admin', 'restrict.reseller_admin', 'restrict.dispatch_admin'])->group(function () {
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
            return redirect()->route('admin.pharmacy.dashboard');
        })->name('admin');

        // Dispatch – Orders only (view, update status, tracking, print)
        Route::get('/admin/dispatch/orders', [DispatchOrderController::class, 'index'])->name('admin.dispatch.orders.index');
        Route::get('/admin/dispatch/orders/{order}', [DispatchOrderController::class, 'show'])->name('admin.dispatch.orders.show');
        Route::post('/admin/dispatch/orders/{order}/status', [DispatchOrderController::class, 'updateStatus'])->name('admin.dispatch.orders.update-status');
        Route::post('/admin/dispatch/orders/{order}/tracking', [DispatchOrderController::class, 'updateTracking'])->name('admin.dispatch.orders.update-tracking');
        Route::get('/admin/dispatch/orders/{order}/invoice', [DispatchOrderController::class, 'invoice'])->name('admin.dispatch.orders.invoice');
        Route::get('/admin/dispatch/orders/{order}/delivery-note', [DispatchOrderController::class, 'deliveryNote'])->name('admin.dispatch.orders.delivery-note');
        Route::get('/admin/dispatch/orders/{order}/shipment-label', [DispatchOrderController::class, 'shipmentLabel'])->name('admin.dispatch.orders.shipment-label');

        // Pharmacy Dashboard & Reports
        Route::get('/admin/pharmacy', [PharmacyDashboardController::class, 'index'])->name('admin.pharmacy.dashboard');
        Route::get('/admin/pharmacy/reports', [PharmacyReportsController::class, 'index'])->name('admin.pharmacy.reports');
        Route::get('/admin/pharmacy/reports/export/pdf', [PharmacyReportsController::class, 'exportPdf'])->name('admin.pharmacy.reports.export.pdf');
        Route::get('/admin/pharmacy/reports/export/excel', [PharmacyReportsController::class, 'exportExcel'])->name('admin.pharmacy.reports.export.excel');

        // Users CRUD
        Route::get('/admin/users', [SuperAdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/create', [SuperAdminUserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users', [SuperAdminUserController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/users/{user}/edit', [SuperAdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/admin/users/{user}', [SuperAdminUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [SuperAdminUserController::class, 'destroy'])->name('admin.users.destroy');

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

        // Banks CRUD (Super Admin, Wholesale Staff, Accountant)
        Route::middleware('role:super_admin,wholesale_staff,accountant')->group(function () {
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
        Route::post('/admin/invoices', [SuperAdminInvoiceController::class, 'store'])->name('admin.invoices.store');
        Route::get('/admin/invoices/{invoice}/edit', [SuperAdminInvoiceController::class, 'edit'])->name('admin.invoices.edit');
        Route::put('/admin/invoices/{invoice}', [SuperAdminInvoiceController::class, 'update'])->name('admin.invoices.update');
        Route::delete('/admin/invoices/{invoice}', [SuperAdminInvoiceController::class, 'destroy'])->name('admin.invoices.destroy');
        Route::get('/admin/invoices/{invoice}/pdf', [SuperAdminInvoiceController::class, 'pdf'])->name('admin.invoices.pdf');

        // Purchases (Purchase Invoices)
        Route::get('/admin/purchases', [SuperAdminPurchaseController::class, 'index'])->name('admin.purchases.index');
        Route::get('/admin/purchases/create', [SuperAdminPurchaseController::class, 'create'])->name('admin.purchases.create');
        Route::post('/admin/purchases', [SuperAdminPurchaseController::class, 'store'])->name('admin.purchases.store');
        Route::get('/admin/purchases/{purchase}/edit', [SuperAdminPurchaseController::class, 'edit'])->name('admin.purchases.edit');
        Route::put('/admin/purchases/{purchase}', [SuperAdminPurchaseController::class, 'update'])->name('admin.purchases.update');
        Route::delete('/admin/purchases/{purchase}', [SuperAdminPurchaseController::class, 'destroy'])->name('admin.purchases.destroy');
    });

    // Accountant (and Super Admin) - Banks, Wallet Management & Approvals
    Route::middleware('role:accountant,super_admin')->group(function () {
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
