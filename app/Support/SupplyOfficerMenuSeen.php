<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Marks Supply Officer sidebar items as viewed so their count drops off.
 */
class SupplyOfficerMenuSeen
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
            'supply-officer.incoming-ris' => self::captureRisList($request),
            'supply-officer.incoming-ris.show', 'supply-officer.dashboard.preview-ris' => self::markRis((int) ($request->route('risId') ?: $request->route('ris'))),
            'supply-officer.atp.index' => self::captureAtpList($request),
            'supply-officer.atp.show', 'supply-officer.atp.edit', 'supply-officer.dashboard.preview-atp' => self::markAtp((int) ($request->route('id') ?: $request->route('atp'))),
            default => null,
        };
    }

    private static function captureRisList(Request $request): void
    {
        if ($request->filled('ris')) {
            self::markRis((int) $request->query('ris'));

            return;
        }

        PurchaserMenuSeen::remember(SupplyOfficerSidebarCounts::KIND_RIS, SupplyOfficerSidebarCounts::incomingRis());
    }

    private static function captureAtpList(Request $request): void
    {
        if ($request->filled('record') || $request->filled('atp')) {
            self::markAtp((int) ($request->query('record') ?: $request->query('atp')));

            return;
        }

        $view = (string) $request->query('view', '');
        if ($view === 'archive') {
            return;
        }

        PurchaserMenuSeen::remember(SupplyOfficerSidebarCounts::KIND_ATP, SupplyOfficerSidebarCounts::actionableAtp());
    }

    private static function markRis(int $id): void
    {
        if ($id < 1) {
            return;
        }

        $rows = SupplyOfficerSidebarCounts::incomingRis();
        if (array_key_exists($id, $rows)) {
            PurchaserMenuSeen::remember(SupplyOfficerSidebarCounts::KIND_RIS, [$id => $rows[$id]]);
        }
    }

    private static function markAtp(int $id): void
    {
        if ($id < 1) {
            return;
        }

        $rows = SupplyOfficerSidebarCounts::actionableAtp();
        if (array_key_exists($id, $rows)) {
            PurchaserMenuSeen::remember(SupplyOfficerSidebarCounts::KIND_ATP, [$id => $rows[$id]]);
        }
    }
}
