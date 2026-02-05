<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dispatch role can only access: admin home, dispatch orders, and products index (view only).
 * Cannot: edit product price, financial reports, wallet, invoice edit, users, categories, suppliers, purchases, banks.
 */
class RestrictDispatchAdmin
{
    protected array $allowedRouteNames = [
        'admin',
        'admin.dispatch.orders.index',
        'admin.dispatch.orders.show',
        'admin.dispatch.orders.update-status',
        'admin.dispatch.orders.update-tracking',
        'admin.dispatch.orders.invoice',
        'admin.dispatch.orders.delivery-note',
        'admin.dispatch.orders.shipment-label',
        'admin.products.index',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role?->name === 'super_admin') {
            return $next($request);
        }

        if ($user->role?->name !== 'dispatch') {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $allowed = in_array($routeName, $this->allowedRouteNames, true)
            || str_starts_with($routeName, 'admin.dispatch.');

        if ($allowed) {
            return $next($request);
        }

        abort(403, 'Access denied. Dispatch can only access Orders and view Products.');
    }
}
