<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When user is wholesale_staff, allow only admin dashboard, pharmacy reports, and users.
 * Super admin can access everything (no restriction).
 */
class RestrictWholesaleStaffAdmin
{
    protected array $allowedRouteNames = [
        'admin',
        'admin.pharmacy.dashboard',
        'admin.pharmacy.reports',
        'admin.pharmacy.reports.export.pdf',
        'admin.pharmacy.reports.export.excel',
        'admin.users.index',
        'admin.users.create',
        'admin.users.store',
        'admin.users.edit',
        'admin.users.update',
        'admin.users.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role?->name === 'super_admin') {
            return $next($request);
        }

        if ($user->role?->name !== 'wholesale_staff') {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $allowed = in_array($routeName, $this->allowedRouteNames, true)
            || str_starts_with($routeName, 'admin.pharmacy.reports')
            || str_starts_with($routeName, 'admin.users')
            || str_starts_with($routeName, 'admin.invoices');

        if ($allowed) {
            return $next($request);
        }

        abort(403, 'Access denied. Wholesale staff can only access Dashboard, Reports, Users, and Invoices.');
    }
}
