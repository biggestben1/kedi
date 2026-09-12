<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accountant can access: office reports hub, pharmacy reports, financial report, invoice view/PDF,
 * wallet management, banks, user management (cashiers/distributors), KD tools, and kit purchase.
 */
class RestrictAccountantAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role?->name !== 'accountant') {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $allowed = $routeName === 'admin'
            || str_starts_with($routeName, 'admin.pharmacy.reports')
            || str_starts_with($routeName, 'admin.pharmacy.financial')
            || str_starts_with($routeName, 'admin.accountant.office-reports')
            || in_array($routeName, ['admin.invoices.show', 'admin.invoices.pdf'], true)
            || str_starts_with($routeName, 'admin.accountant.wallet')
            || str_starts_with($routeName, 'admin.wallet_topups')
            || str_starts_with($routeName, 'admin.banks')
            || str_starts_with($routeName, 'admin.users')
            || str_starts_with($routeName, 'admin.kd')
            || str_starts_with($routeName, 'admin.kedi-kits.purchase');

        if ($allowed) {
            return $next($request);
        }

        abort(403, 'Access denied.');
    }
}
