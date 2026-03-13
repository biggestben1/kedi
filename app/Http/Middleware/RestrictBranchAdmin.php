<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Branch role can only access: their users (Annex), their stock, invoices (for their Annex users).
 */
class RestrictBranchAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role?->name !== 'branch') {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $allowed = $routeName === 'admin'
            || str_starts_with($routeName, 'admin.users')
            || str_starts_with($routeName, 'admin.invoices')
            || str_starts_with($routeName, 'admin.branch.stock')
            || str_starts_with($routeName, 'admin.contacts')
            || str_starts_with($routeName, 'admin.products')
            || str_starts_with($routeName, 'admin.pharmacy')
            || str_starts_with($routeName, 'admin.dispatch.orders')
            || str_starts_with($routeName, 'admin.categories')
            || str_starts_with($routeName, 'admin.banks')
            || str_starts_with($routeName, 'admin.wallet_topups')
            || str_starts_with($routeName, 'admin.accountant.wallet')
            || str_starts_with($routeName, 'admin.back_orders')
            || str_starts_with($routeName, 'admin.kd')
            || str_starts_with($routeName, 'admin.kedi-kits.purchase');

        if ($allowed) {
            return $next($request);
        }

        abort(403, 'Access denied.');
    }
}
