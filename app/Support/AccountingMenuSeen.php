<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Marks Accounting sidebar items as viewed so their count drops off.
 */
class AccountingMenuSeen
{
    public static function capture(Request $request): void
    {
        if (! $request->isMethod('GET') || ! Auth::check() || ! Schema::hasTable(PurchaserMenuSeen::TABLE)) {
            return;
        }

        if ($request->filled('search')) {
            return;
        }

        $name = $request->route()?->getName();

        match ($name) {
            'accounting.dv.index' => self::captureDvIndex($request),
            'accounting.dv.show' => self::markDv((int) $request->route('id')),
            'accounting.liq.index' => self::captureLiqIndex($request),
            'accounting.dashboard.preview' => self::capturePreview($request),
            default => self::captureUnnamed($request, $name),
        };
    }

    private static function captureUnnamed(Request $request, ?string $name): void
    {
        // Liquidation show has no named route.
        if (preg_match('#^accounting/liquidation-reports/(\d+)$#', ltrim($request->path(), '/'), $m)) {
            self::markLiq((int) $m[1]);
        }
    }

    private static function captureDvIndex(Request $request): void
    {
        if ($request->filled('view_dv') || $request->filled('edit_dv')) {
            $id = (int) ($request->query('view_dv') ?: $request->query('edit_dv'));
            self::markDv($id);

            return;
        }

        if ($request->filled('rfc')) {
            $rfcId = (int) $request->query('rfc');
            $pkgs = AccountingSidebarCounts::dvPackages();
            if (array_key_exists($rfcId, $pkgs)) {
                PurchaserMenuSeen::remember(AccountingSidebarCounts::KIND_DV_PKG, [$rfcId => $pkgs[$rfcId]]);
            }
            $dv = AccountingHandoff::dvForRfc($rfcId);
            if ($dv) {
                self::markDv((int) $dv->dv_id);
            }

            return;
        }

        $status = strtolower(trim((string) $request->query('status', 'all')));
        if (in_array($status, ['incoming', ''], true)) {
            PurchaserMenuSeen::remember(AccountingSidebarCounts::KIND_DV_PKG, AccountingSidebarCounts::dvPackages());
            $drafts = array_filter(
                AccountingSidebarCounts::dvActionable(),
                fn ($s) => in_array($s, ['Draft', 'Revision'], true)
            );
            PurchaserMenuSeen::remember(AccountingSidebarCounts::KIND_DV, $drafts);
        } elseif ($status === 'ready_release') {
            $ready = array_filter(
                AccountingSidebarCounts::dvActionable(),
                fn ($s) => $s === 'President Approved'
            );
            PurchaserMenuSeen::remember(AccountingSidebarCounts::KIND_DV, $ready);
        }
    }

    private static function captureLiqIndex(Request $request): void
    {
        $status = strtolower(trim((string) $request->query('status', 'incoming')));
        $focus = (string) $request->query('focus', '');
        if ($status !== '' && $status !== 'incoming' && $focus === '') {
            return;
        }

        PurchaserMenuSeen::remember(AccountingSidebarCounts::KIND_LIQ, AccountingSidebarCounts::liqIncoming());
    }

    private static function capturePreview(Request $request): void
    {
        $type = strtolower((string) ($request->route('type') ?? ''));
        $id = (int) ($request->route('id') ?? 0);
        if ($type === 'dv') {
            self::markDv($id);
        } elseif ($type === 'liq') {
            self::markLiq($id);
        }
    }

    private static function markDv(int $id): void
    {
        if ($id < 1) {
            return;
        }

        $rows = AccountingSidebarCounts::dvActionable();
        if (array_key_exists($id, $rows)) {
            PurchaserMenuSeen::remember(AccountingSidebarCounts::KIND_DV, [$id => $rows[$id]]);
        }
    }

    private static function markLiq(int $id): void
    {
        if ($id < 1) {
            return;
        }

        $rows = AccountingSidebarCounts::liqIncoming();
        if (array_key_exists($id, $rows)) {
            PurchaserMenuSeen::remember(AccountingSidebarCounts::KIND_LIQ, [$id => $rows[$id]]);
        }
    }
}
