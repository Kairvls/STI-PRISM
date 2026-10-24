<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Marks school administrator sidebar items as viewed so their count drops off.
 */
class SchoolAdminMenuSeen
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
            'school-admin.procurement-review' => self::captureRisAccept($request),
            'school-admin.digital-signatures.sign-ris' => self::captureRisSign($request),
            'school-admin.procurement-review.ris.print', 'school-admin.ris.show' => self::markRis((int) ($request->route('ris') ?: $request->route('id'))),
            'school-admin.atp.index' => self::captureIncoming($request, 'atp-review', SchoolAdminSidebarCounts::atpIncoming(), SchoolAdminSidebarCounts::KIND_ATP),
            'school-admin.atp.show' => self::markOne((int) $request->route('id'), SchoolAdminSidebarCounts::atpIncoming(), SchoolAdminSidebarCounts::KIND_ATP),
            'school-admin.rfc.index' => self::captureIncoming($request, 'rfc-review', SchoolAdminSidebarCounts::rfcIncoming(), SchoolAdminSidebarCounts::KIND_RFC),
            'school-admin.rfc.show' => self::markOne((int) $request->route('id'), SchoolAdminSidebarCounts::rfcIncoming(), SchoolAdminSidebarCounts::KIND_RFC),
            'school-admin.dv.index' => self::captureDv($request),
            'school-admin.dv.show' => self::markOne((int) $request->route('id'), SchoolAdminSidebarCounts::dvIncoming(), SchoolAdminSidebarCounts::KIND_DV),
            default => null,
        };
    }

    private static function captureRisAccept(Request $request): void
    {
        if ($request->filled('ris')) {
            self::markRis((int) $request->query('ris'));

            return;
        }

        $filter = strtolower((string) $request->query('filter', 'pending'));
        $focus = (string) $request->query('focus', '');
        if (! in_array($filter, ['pending', ''], true) || $focus === AdminAttentionSummary::FOCUS_AMENDMENTS) {
            return;
        }

        PurchaserMenuSeen::remember(SchoolAdminSidebarCounts::KIND_RIS_ACCEPT, SchoolAdminSidebarCounts::risAccept());
    }

    private static function captureRisSign(Request $request): void
    {
        if ($request->filled('ris')) {
            self::markRis((int) $request->query('ris'));

            return;
        }

        $filter = strtolower((string) $request->query('filter', 'pending'));
        if (in_array($filter, ['all', 'cosigned', 'president_rejected'], true)) {
            return;
        }

        $rows = SchoolAdminSidebarCounts::risSign();
        if ($filter === 'for_decision') {
            $rows = array_filter($rows, fn ($status) => $status === RisWorkflow::ACCEPTED);
        } elseif ($filter === 'for_cosign') {
            $rows = array_filter($rows, fn ($status) => $status !== RisWorkflow::ACCEPTED);
        }

        PurchaserMenuSeen::remember(SchoolAdminSidebarCounts::KIND_RIS_SIGN, $rows);
    }

    private static function markRis(int $id): void
    {
        if ($id < 1) {
            return;
        }

        $accept = SchoolAdminSidebarCounts::risAccept();
        if (array_key_exists($id, $accept)) {
            PurchaserMenuSeen::remember(SchoolAdminSidebarCounts::KIND_RIS_ACCEPT, [$id => $accept[$id]]);

            return;
        }

        $sign = SchoolAdminSidebarCounts::risSign();
        if (array_key_exists($id, $sign)) {
            PurchaserMenuSeen::remember(SchoolAdminSidebarCounts::KIND_RIS_SIGN, [$id => $sign[$id]]);
        }
    }

    /**
     * @param  array<int, string>  $rows
     */
    private static function captureIncoming(Request $request, string $focusKey, array $rows, string $kind): void
    {
        $focus = (string) $request->query('focus', '');
        if ($focus !== '' && $focus !== $focusKey) {
            return;
        }

        $status = strtolower(trim((string) $request->query('status', 'incoming')));
        if ($status !== '' && $status !== 'incoming') {
            return;
        }

        PurchaserMenuSeen::remember($kind, $rows);
    }

    private static function captureDv(Request $request): void
    {
        $status = strtolower(trim((string) $request->query('status', 'incoming')));
        if ($status !== '' && $status !== 'incoming') {
            return;
        }

        PurchaserMenuSeen::remember(SchoolAdminSidebarCounts::KIND_DV, SchoolAdminSidebarCounts::dvIncoming());
    }

    /**
     * @param  array<int, string>  $rows
     */
    private static function markOne(int $id, array $rows, string $kind): void
    {
        if ($id < 1 || ! array_key_exists($id, $rows)) {
            return;
        }

        PurchaserMenuSeen::remember($kind, [$id => $rows[$id]]);
    }
}
