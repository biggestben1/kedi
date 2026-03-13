<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Annex role can access: admin, pharmacy dashboard, pharmacy reports,
 * users (create Accountant/Dispatch), products (view only), invoices (own), back orders (own).
 * Annex cannot create/edit invoices - they are the customer; Service Center creates for them.
 */
class RestrictAnnexAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role?->name !== 'annex') {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $allowed = $routeName === 'admin'
            || str_starts_with($routeName, 'admin.pharmacy.dashboard')
            || str_starts_with($routeName, 'admin.pharmacy.reports')
            || $routeName === 'admin.invoices.index'
            || $routeName === 'admin.invoices.show'
            || $routeName === 'admin.invoices.pdf'
            || str_starts_with($routeName, 'admin.users')
            || str_starts_with($routeName, 'admin.back_orders')
            || $routeName === 'admin.products.index'
            || (str_starts_with($routeName, 'admin.kd') && !str_contains($routeName, 'dpbv'))
            || str_starts_with($routeName, 'admin.kedi-kits.purchase');

        if ($allowed) {
            return $next($request);
        }

        abort(403, 'Access denied.');
    }
}
