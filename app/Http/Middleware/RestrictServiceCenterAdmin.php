<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service Center role can access: admin, invoices, users (create Annex/Dispatch/Accountant),
 * banks, wallet management, top-up approvals, back orders, products (view only, no edit).
 */
class RestrictServiceCenterAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role?->name !== 'service_center') {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $allowed = $routeName === 'admin'
            || str_starts_with($routeName, 'admin.pharmacy.dashboard')
            || str_starts_with($routeName, 'admin.pharmacy.reports')
            || str_starts_with($routeName, 'admin.invoices')
            || str_starts_with($routeName, 'admin.users')
            || str_starts_with($routeName, 'admin.banks')
            || str_starts_with($routeName, 'admin.wallet_topups')
            || str_starts_with($routeName, 'admin.accountant.wallet')
            || str_starts_with($routeName, 'admin.back_orders')
            || $routeName === 'admin.products.index'
            || str_starts_with($routeName, 'admin.kd')
            || str_starts_with($routeName, 'admin.kedi-kits.purchase');

        if ($allowed) {
            return $next($request);
        }

        abort(403, 'Access denied.');
    }
}
