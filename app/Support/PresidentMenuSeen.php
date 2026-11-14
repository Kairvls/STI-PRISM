<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Marks President sidebar items as viewed so their count drops off.
 */
class PresidentMenuSeen
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
            'president.approvals' => self::captureApprovals($request),
            'president.direct-approvals' => self::captureDirect($request),
            'president.direct-approvals.proof' => self::markRis((int) $request->route('ris'), PresidentSidebarCounts::KIND_DIRECT, PresidentSidebarCounts::directApprovals()),
            'president.dv.index' => self::captureDvIndex($request),
            'president.dv.show' => self::markDv((int) $request->route('id')),
            'president.dashboard.preview' => self::capturePreview($request),
            default => null,
        };
    }

    private static function captureApprovals(Request $request): void
    {
        if ($request->filled('ris')) {
            self::markAnyRis((int) $request->query('ris'));

            return;
        }

        $focus = (string) $request->query('focus', '');
        if ($focus === PresidentAttentionSummary::FOCUS_AWAITING_NOTIFY) {
            PurchaserMenuSeen::remember(PresidentSidebarCounts::KIND_NOTIFY, PresidentSidebarCounts::risNotify());

            return;
        }

        if ($focus === '' || $focus === PresidentAttentionSummary::FOCUS_AWAITING_APPROVAL) {
            PurchaserMenuSeen::remember(PresidentSidebarCounts::KIND_RIS, PresidentSidebarCounts::risPending());
            if ($focus === '') {
                PurchaserMenuSeen::remember(PresidentSidebarCounts::KIND_NOTIFY, PresidentSidebarCounts::risNotify());
            }
        }
    }

    private static function captureDirect(Request $request): void
    {
        if ($request->filled('ris')) {
            self::markRis((int) $request->query('ris'), PresidentSidebarCounts::KIND_DIRECT, PresidentSidebarCounts::directApprovals());

            return;
        }

        PurchaserMenuSeen::remember(PresidentSidebarCounts::KIND_DIRECT, PresidentSidebarCounts::directApprovals());
    }

    private static function captureDvIndex(Request $request): void
    {
        $status = strtolower(trim((string) $request->query('status', 'incoming')));
        if ($status !== '' && ! in_array($status, ['incoming', 'with_president', 'pending'], true)) {
            return;
        }

        PurchaserMenuSeen::remember(PresidentSidebarCounts::KIND_DV, PresidentSidebarCounts::dvIncoming());
    }

    private static function capturePreview(Request $request): void
    {
        $type = strtolower((string) $request->route('type'));
        $id = (int) $request->route('id');
        if ($type === 'dv') {
            self::markDv($id);
        }
    }

    private static function markAnyRis(int $id): void
    {
        if ($id < 1) {
            return;
        }

        $pending = PresidentSidebarCounts::risPending();
        if (array_key_exists($id, $pending)) {
            PurchaserMenuSeen::remember(PresidentSidebarCounts::KIND_RIS, [$id => $pending[$id]]);

            return;
        }

        $notify = PresidentSidebarCounts::risNotify();
        if (array_key_exists($id, $notify)) {
            PurchaserMenuSeen::remember(PresidentSidebarCounts::KIND_NOTIFY, [$id => $notify[$id]]);
        }
    }

    /**
     * @param  array<int, string>  $rows
     */
    private static function markRis(int $id, string $kind, array $rows): void
    {
        if ($id < 1 || ! array_key_exists($id, $rows)) {
            return;
        }

        PurchaserMenuSeen::remember($kind, [$id => $rows[$id]]);
    }

    private static function markDv(int $id): void
    {
        if ($id < 1) {
            return;
        }

        $rows = PresidentSidebarCounts::dvIncoming();
        if (array_key_exists($id, $rows)) {
            PurchaserMenuSeen::remember(PresidentSidebarCounts::KIND_DV, [$id => $rows[$id]]);
        }
    }
}
