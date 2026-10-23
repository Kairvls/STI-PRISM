<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Marks receiving sidebar items as viewed so their count drops off.
 */
class ReceivingMenuSeen
{
    public static function capture(Request $request): void
    {
        if (! $request->isMethod('GET') || ! Auth::check() || ! Schema::hasTable(PurchaserMenuSeen::TABLE)) {
            return;
        }

        if ($request->filled('search')) {
            return;
        }

        if ($request->is('receiving/reports')) {
            self::captureReports($request);

            return;
        }

        if ($request->is('receiving/reports/*/print') || $request->is('receiving/dashboard/preview/rr/*')) {
            self::markReport((int) ($request->route('report') ?: $request->route('id')));

            return;
        }

        if ($request->is('receiving/back-orders')) {
            self::captureBackOrders($request);
        }
    }

    private static function captureReports(Request $request): void
    {
        if ($request->filled('date')) {
            return;
        }

        if ($request->filled('rr')) {
            self::markReport((int) $request->query('rr'));

            return;
        }

        $status = (string) $request->query('status', 'queue');
        $focus = (string) $request->query('focus', '');
        if (in_array($status, ['completed', 'returned'], true) || $focus === 'returned') {
            return;
        }

        $rows = ReceivingSidebarCounts::pendingReports($focus === 'leftover');
        PurchaserMenuSeen::remember(ReceivingSidebarCounts::KIND_REPORT, $rows);
    }

    private static function markReport(int $id): void
    {
        if ($id < 1) {
            return;
        }

        $rows = ReceivingSidebarCounts::pendingReports();
        if (! array_key_exists($id, $rows)) {
            return;
        }

        PurchaserMenuSeen::remember(ReceivingSidebarCounts::KIND_REPORT, [$id => $rows[$id]]);
    }

    private static function captureBackOrders(Request $request): void
    {
        if ($request->filled('type') || $request->filled('supplier')) {
            return;
        }

        $status = $request->filled('status') ? (string) $request->query('status') : null;
        if ($status !== null && $status !== 'unresolved' && ! in_array($status, BackOrders::UNRESOLVED, true)) {
            return;
        }

        $bucket = match ($request->query('payment')) {
            'dp' => 'dp',
            'ci' => 'ci',
            'ca' => 'ca',
            default => null,
        };
        $rootReportId = $request->filled('rr') ? (int) $request->query('rr') : null;

        PurchaserMenuSeen::remember(
            ReceivingSidebarCounts::KIND_BACK_ORDER,
            ReceivingSidebarCounts::backOrders($bucket, $status, $rootReportId)
        );
    }
}
