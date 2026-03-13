<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When user is reseller, allow only Users (their customers) and Invoices.
 */
class RestrictResellerAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role?->name !== 'reseller') {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $allowed = $routeName === 'admin'
            || str_starts_with($routeName, 'admin.users')
            || str_starts_with($routeName, 'admin.invoices')
            || str_starts_with($routeName, 'admin.kd');

        if ($allowed) {
            return $next($request);
        }

        abort(403, 'Access denied. Resellers can only access Users, Invoices, and Borrow.');
    }
}
