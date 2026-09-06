<?php

namespace App\Http\Middleware;

use App\Support\ProcurementPortal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maintenance procurement routes require user_can_procurement.
 */
class MaintenanceProcurementMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! ProcurementPortal::userCanAccessProcurement()) {
            abort(403, 'Procurement access is not enabled for this account.');
        }

        return $next($request);
    }
}
