<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Headquarters role can only access: dashboard, reports, all invoices (full CRUD),
 * products (full CRUD), categories (full CRUD), dispatch orders (view), and contact messages.
 */
class RestrictHeadquartersAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role?->name === 'super_admin') {
            return $next($request);
        }

        if ($user->role?->name !== 'headquarters') {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $allowed = $routeName === 'admin'
            || str_starts_with($routeName, 'admin.pharmacy.dashboard')
            || str_starts_with($routeName, 'admin.pharmacy.reports')
            || str_starts_with($routeName, 'admin.invoices')
            || str_starts_with($routeName, 'admin.in-stock')
            || str_starts_with($routeName, 'admin.products')
            || str_starts_with($routeName, 'admin.categories')
            || str_starts_with($routeName, 'admin.dispatch.orders')
            || str_starts_with($routeName, 'admin.contacts')
            || str_starts_with($routeName, 'admin.users')
            || str_starts_with($routeName, 'admin.back_orders')
            || str_starts_with($routeName, 'admin.dpbv')
            || str_starts_with($routeName, 'admin.promo')
            || str_starts_with($routeName, 'admin.bonus')
            || str_starts_with($routeName, 'admin.kd')
            || str_starts_with($routeName, 'admin.kedi-kits.purchase');

        if ($allowed) {
            return $next($request);
        }

        abort(403, 'Access denied. Headquarters can only access Invoices, Products, Categories, Dispatch Orders, DPBV, Promo, Bonus, Borrow, Purchase Kits, and Users.');
    }
}
