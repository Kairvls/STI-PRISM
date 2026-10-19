<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Per-module importance popup: drafts, unsubmitted work, pending review, and
 * "still need to create/send" items for the signed-in role.
 *
 * Mute duration is enforced client-side (localStorage); this class only builds
 * the notice payload for the current request path.
 */
class ModuleImportanceNotice
{
    public const MUTE_OPTIONS = [
        ['minutes' => 30, 'label' => '30 minutes'],
        ['minutes' => 60, 'label' => '1 hour'],
        ['minutes' => 300, 'label' => '5 hours'],
        ['minutes' => 1440, 'label' => '24 hours'],
    ];

    /** @var array<string, mixed>|null */
    private static ?array $cached = null;

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     eyebrow: string,
     *     title: string,
     *     subtitle: string,
     *     total: int,
     *     items: list<array{
     *         key: string,
     *         count: int,
     *         title: string,
     *         description: string,
     *         url: string,
     *         icon: string,
     *         tone: string
     *     }>
     * }|null
     */
    public static function forCurrentRequest(): ?array
    {
        if (self::$cached !== null) {
            return self::$cached === [] ? null : self::$cached;
        }

        if (! Auth::check()) {
            self::$cached = [];

            return null;
        }

        $module = self::detectModule();
        if ($module === null) {
            self::$cached = [];

            return null;
        }

        $items = array_values(array_filter(
            self::itemsFor($module['key']),
            fn ($item) => ($item['count'] ?? 0) > 0
        ));

        if ($items === []) {
            self::$cached = [];

            return null;
        }

        $total = array_sum(array_column($items, 'count'));

        self::$cached = [
            'key' => $module['key'],
            'label' => $module['label'],
            'eyebrow' => 'Module importance',
            'title' => $module['label'].' needs attention',
            'subtitle' => self::subtitleFor($module['key']),
            'total' => $total,
            'items' => $items,
        ];

        return self::$cached;
    }

    /**
     * Role-aware helper text under the popup title.
     */
    private static function subtitleFor(string $moduleKey): string
    {
        return match (true) {
            str_starts_with($moduleKey, 'school-admin.') => 'Items waiting for your review or signature in this module.',
            str_starts_with($moduleKey, 'president.') => 'Items waiting for your approval or next step in this module.',
            str_starts_with($moduleKey, 'accounting.') => 'Drafts to finish or items waiting for your action in this module.',
            str_starts_with($moduleKey, 'receiving.') => 'Receiving reports waiting for your verification in this module.',
            str_starts_with($moduleKey, 'supply.') => 'Incoming work or drafts waiting for you in this module.',
            $moduleKey === 'maintenance.schedules' => 'Overdue equipment maintenance that still needs to be completed.',
            str_starts_with($moduleKey, 'maintenance.') => 'Reports that still need your attention in this module.',
            default => 'Drafts, unsubmitted work, or next steps waiting on you in this module.',
        };
    }

    /**
     * Lightweight module registry for mute labels in the topbar (no DB queries).
     *
     * @return array<string, string> moduleKey => label
     */
    public static function moduleLabels(): array
    {
        return [
            'purchaser.ris' => 'RIS',
            'purchaser.atp' => 'Authority to Purchase',
            'purchaser.po' => 'Purchase Orders',
            'purchaser.rfc' => 'Funding Requests',
            'purchaser.rr' => 'Receiving Reports',
            'purchaser.liq' => 'Liquidation Reports',
            'supply.incoming-ris' => 'Incoming RIS',
            'supply.atp' => 'Authority to Purchase',
            'school-admin.ris' => 'RIS Requests',
            'school-admin.atp' => 'ATP Requests',
            'school-admin.rfc' => 'Check Approval Requests',
            'school-admin.dv' => 'DV Requests',
            'accounting.liq' => 'Liquidation Reports',
            'accounting.dv' => 'Disbursement Vouchers',
            'president.ris' => 'RIS approvals',
            'president.dv' => 'Disbursement Vouchers',
            'receiving.rr' => 'Receiving Reports',
            'maintenance.reports' => 'Reports',
            'maintenance.schedules' => 'Maintenance Schedule',
        ];
    }

    /**
     * @return array{key: string, label: string}|null
     */
    private static function detectModule(): ?array
    {
        $labels = self::moduleLabels();

        $match = match (true) {
            // List only — not Canvas+RIS reference views (/incoming-ris/{id}).
            request()->is('supply-officer/incoming-ris') => 'supply.incoming-ris',
            request()->is('supply-officer/authority-to-purchase*') => 'supply.atp',
            request()->is('purchaser/ris*') => 'purchaser.ris',
            request()->is('purchaser/authority-to-purchase*') => 'purchaser.atp',
            request()->is('purchaser/purchase-orders*') => 'purchaser.po',
            request()->is('purchaser/request-check*') => 'purchaser.rfc',
            request()->is('purchaser/receiving-reports*') => 'purchaser.rr',
            request()->is('purchaser/liquidation-reports*') => 'purchaser.liq',
            request()->is('school-admin/digital-signatures/sign-ris*', 'school-admin/procurement-review*', 'admin/digital-signatures/sign-ris*', 'admin/procurement-review*') => 'school-admin.ris',
            request()->is('school-admin/authority-to-purchase*', 'admin/authority-to-purchase*') => 'school-admin.atp',
            request()->is('school-admin/request-check*', 'admin/request-check*') => 'school-admin.rfc',
            request()->is('school-admin/disbursement-vouchers*', 'admin/disbursement-vouchers*') => 'school-admin.dv',
            request()->is('accounting/liquidation-reports*') => 'accounting.liq',
            request()->is('accounting/disbursement-vouchers*') => 'accounting.dv',
            request()->is('president/approvals*') => 'president.ris',
            request()->is('president/disbursement-vouchers*') => 'president.dv',
            request()->is('receiving/reports*', 'receiving-officer/reports*') => 'receiving.rr',
            request()->is('maintenance/reports*') => 'maintenance.reports',
            request()->is('maintenance/schedules*', 'admin/schedules*') => 'maintenance.schedules',
            default => null,
        };

        if ($match === null || ! isset($labels[$match])) {
            return null;
        }

        return ['key' => $match, 'label' => $labels[$match]];
    }

    /**
     * @return list<array{key: string, count: int, title: string, description: string, url: string, icon: string, tone: string}>
     */
    private static function itemsFor(string $moduleKey): array
    {
        return match ($moduleKey) {
            'purchaser.ris' => self::purchaserRisItems(),
            'purchaser.atp' => self::purchaserAtpItems(),
            'purchaser.po' => self::purchaserPoItems(),
            'purchaser.rfc' => self::purchaserRfcItems(),
            'purchaser.rr' => self::purchaserRrItems(),
            'purchaser.liq' => self::purchaserLiqItems(),
            'supply.incoming-ris' => self::supplyIncomingRisItems(),
            'supply.atp' => self::supplyAtpItems(),
            'school-admin.ris' => self::schoolAdminRisItems(),
            'school-admin.atp' => self::schoolAdminAtpItems(),
            'school-admin.rfc' => self::schoolAdminRfcItems(),
            'school-admin.dv' => self::schoolAdminDvItems(),
            'accounting.liq' => self::accountingLiqItems(),
            'accounting.dv' => self::accountingDvItems(),
            'president.ris' => self::presidentRisItems(),
            'president.dv' => self::presidentDvItems(),
            'receiving.rr' => self::receivingRrItems(),
            'maintenance.reports' => self::maintenanceReportItems(),
            'maintenance.schedules' => self::maintenanceScheduleItems(),
            default => [],
        };
    }

    private static function item(
        string $key,
        int $count,
        string $title,
        string $description,
        string $url,
        string $icon = 'circle-alert',
        string $tone = 'amber'
    ): array {
        return compact('key', 'count', 'title', 'description', 'url', 'icon', 'tone');
    }

    private static function notArchived($query, string $column): void
    {
        $parts = explode('.', $column);
        $table = count($parts) === 2 ? $parts[0] : null;
        $col = count($parts) === 2 ? $parts[1] : $parts[0];

        if ($table && ! Schema::hasColumn($table, $col)) {
            return;
        }

        $query->where(function ($q) use ($column) {
            $q->whereNull($column)->orWhere($column, 0);
        });
    }

    private static function safeRoute(string $name, array $params = [], string $fallback = '#'): string
    {
        try {
            if (class_exists(AdminPortal::class) && str_starts_with($name, 'school-admin.')) {
                $suffix = substr($name, strlen('school-admin.'));

                return AdminPortal::route($suffix, $params);
            }

            return route($name, $params);
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private static function portalRoute(string $suffix, array $params = []): string
    {
        try {
            return ProcurementPortal::route($suffix, $params);
        } catch (\Throwable $e) {
            return '#';
        }
    }

    /** @return list<array> */
    private static function purchaserRisItems(): array
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return [];
        }

        $base = DB::table('requisition_issue_slip_table');
        PurchaserDocumentAccess::scopeOwned($base, 'ris', 'requisition_issue_slip_table');
        self::notArchived($base, 'requisition_issue_slip_table.ris_is_archived');

        $drafts = (int) (clone $base)->where('ris_status', 'Draft')->count();
        $revision = (int) (clone $base)->where('ris_status', 'Minor Revision')->count();
        $attention = PurchaserAttentionSummary::counts();
        $readySend = (int) ($attention['risReadyForAtp'] ?? 0);

        return [
            self::item(
                'drafts',
                $drafts,
                $drafts.' draft '.Str::plural('RIS', $drafts),
                'Finish and submit these slips to the School Administrator.',
                self::portalRoute('ris.index', ['status' => 'Draft']),
                'file-pen',
                'amber'
            ),
            self::item(
                'revision',
                $revision,
                $revision.' RIS need'.($revision === 1 ? 's' : '').' resubmit',
                'Returned for minor revision — update and resubmit.',
                self::portalRoute('ris.index', ['status' => 'Minor Revision']),
                'rotate-ccw',
                'rose'
            ),
            self::item(
                'send-supply',
                $readySend,
                $readySend.' approved '.Str::plural('RIS', $readySend).' ready to send',
                'Send Canvas + RIS to the School Supply / Material Officer next.',
                self::portalRoute('ris.index', ['focus' => PurchaserAttentionSummary::FOCUS_RIS_READY_FOR_ATP]),
                'send',
                'blue'
            ),
        ];
    }

    /** @return list<array> */
    private static function purchaserAtpItems(): array
    {
        $attention = PurchaserAttentionSummary::counts();
        $ready = (int) ($attention['atpReadyForRfc'] ?? 0);

        return [
            self::item(
                'create-rfc',
                $ready,
                $ready.' approved '.Str::plural('ATP', $ready).' need funding',
                'Choose a payment path and create the funding request.',
                self::portalRoute('atp.index', ['focus' => PurchaserAttentionSummary::FOCUS_ATP_READY_FOR_RFC]),
                'banknote',
                'blue'
            ),
        ];
    }

    /** @return list<array> */
    private static function purchaserPoItems(): array
    {
        if (! Schema::hasTable('purchase_orders_table')) {
            return [];
        }

        $base = DB::table('purchase_orders_table');
        if (Schema::hasColumn('purchase_orders_table', 'purchase_order_created_by')) {
            $base->where('purchase_order_created_by', Auth::id());
        }
        self::notArchived($base, 'purchase_orders_table.purchase_order_is_archived');

        $drafts = (int) (clone $base)->where('purchase_order_status', 'Draft')->count();

        return [
            self::item(
                'drafts',
                $drafts,
                $drafts.' draft '.Str::plural('purchase order', $drafts),
                'Complete the basket and submit for approval.',
                self::safeRoute('purchaser.purchase-orders.index', ['status' => 'Draft']),
                'file-pen',
                'amber'
            ),
        ];
    }

    /** @return list<array> */
    private static function purchaserRfcItems(): array
    {
        if (! Schema::hasTable('request_check_table')) {
            return [];
        }

        $base = DB::table('request_check_table');
        PurchaserDocumentAccess::scopeOwned($base, 'rfc', 'request_check_table');
        self::notArchived($base, 'request_check_table.request_check_is_archived');

        $drafts = (int) (clone $base)->whereIn('request_check_status', ['Draft', 'Minor Revision'])->count();
        $attention = PurchaserAttentionSummary::counts();
        $checkReady = (int) ($attention['checkReady'] ?? 0);
        $rrReady = (int) ($attention['rfcReadyForRr'] ?? 0);

        $canSend = 0;
        if (AccountingHandoff::rfcHandoffColumnsReady()) {
            $canSend = (int) (clone $base)
                ->where('request_check_status', 'Approved')
                ->whereNull('request_check_sent_to_accounting_at')
                ->count();
        }

        return [
            self::item(
                'drafts',
                $drafts,
                $drafts.' unsubmitted funding '.Str::plural('request', $drafts),
                'Draft or revision requests still need to be submitted.',
                self::portalRoute('rfc.index', ['status' => 'Draft']),
                'file-pen',
                'amber'
            ),
            self::item(
                'send-accounting',
                $canSend,
                $canSend.' approved '.Str::plural('request', $canSend).' ready for Accounting',
                'Send the package so Accounting can prepare the disbursement voucher.',
                self::portalRoute('rfc.index', ['status' => 'Approved']),
                'send',
                'blue'
            ),
            self::item(
                'check-ready',
                $checkReady,
                $checkReady.' '.Str::plural('check', $checkReady).' ready for pickup',
                'Collect the check, buy, then create the Receiving Report.',
                self::portalRoute('rfc.index', ['focus' => PurchaserAttentionSummary::FOCUS_CHECK_READY]),
                'banknote',
                'emerald'
            ),
            self::item(
                'create-rr',
                $rrReady,
                $rrReady.' funded '.Str::plural('request', $rrReady).' need a Receiving Report',
                'Create the Receiving Report after buying with the released check.',
                self::portalRoute('rfc.index', ['focus' => PurchaserAttentionSummary::FOCUS_RFC_READY_FOR_RR]),
                'package-plus',
                'blue'
            ),
        ];
    }

    /** @return list<array> */
    private static function purchaserRrItems(): array
    {
        if (! Schema::hasTable('receiving_reports_table')) {
            return [];
        }

        $base = DB::table('receiving_reports_table');
        PurchaserDocumentAccess::scopeOwned($base, 'rr', 'receiving_reports_table');
        self::notArchived($base, 'receiving_reports_table.receiving_report_is_archived');

        $drafts = (int) (clone $base)->whereIn('receiving_report_status', ['Draft', 'Minor Revision'])->count();
        $attention = PurchaserAttentionSummary::counts();
        $liqReady = (int) ($attention['rrReadyForLiq'] ?? 0);

        return [
            self::item(
                'drafts',
                $drafts,
                $drafts.' unsubmitted '.Str::plural('Receiving Report', $drafts),
                'Finish drafts / revisions and submit to the Receiving Officer.',
                self::portalRoute('rr.index', ['status' => 'Draft']),
                'file-pen',
                'amber'
            ),
            self::item(
                'create-liq',
                $liqReady,
                $liqReady.' completed '.Str::plural('Receiving Report', $liqReady).' need liquidation',
                'For Cash Advance — create the Liquidation Report next.',
                self::portalRoute('rr.index', ['focus' => PurchaserAttentionSummary::FOCUS_RR_READY_FOR_LIQ]),
                'receipt',
                'violet'
            ),
        ];
    }

    /** @return list<array> */
    private static function purchaserLiqItems(): array
    {
        if (! Schema::hasTable('liquidation_reports_table')) {
            return [];
        }

        $base = DB::table('liquidation_reports_table');
        PurchaserDocumentAccess::scopeOwned($base, 'liq', 'liquidation_reports_table');
        self::notArchived($base, 'liquidation_reports_table.liquidation_report_is_archived');

        $drafts = (int) (clone $base)->whereIn('liquidation_report_status', ['Draft', 'Minor Revision'])->count();

        return [
            self::item(
                'drafts',
                $drafts,
                $drafts.' unsubmitted '.Str::plural('liquidation', $drafts),
                'Complete and submit to Accounting.',
                self::portalRoute('liq.index', ['status' => 'Draft']),
                'file-pen',
                'amber'
            ),
        ];
    }

    /** @return list<array> */
    private static function supplyIncomingRisItems(): array
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return [];
        }

        $count = (int) SupplyHandoff::scopeIncomingRisForSupply(
            DB::table('requisition_issue_slip_table')
        )->count();

        return [
            self::item(
                'create-atp',
                $count,
                $count.' incoming '.Str::plural('RIS', $count).' need an ATP',
                'Create an Authority to Purchase from each approved Canvas + RIS.',
                self::safeRoute('supply-officer.incoming-ris'),
                'file-plus-2',
                'blue'
            ),
        ];
    }

    /** @return list<array> */
    private static function supplyAtpItems(): array
    {
        if (! Schema::hasTable('authority_to_purchase_table')) {
            return [];
        }

        $base = DB::table('authority_to_purchase_table');
        PurchaserDocumentAccess::scopeOwned($base, 'atp', 'authority_to_purchase_table');
        self::notArchived($base, 'authority_to_purchase_table.authority_purchase_is_archived');

        $drafts = (int) (clone $base)->whereIn('authority_purchase_status', ['Draft', 'Minor Revision'])->count();

        $releaseReady = 0;
        if (SupplyHandoff::atpReleaseColumnsReady()) {
            $releaseReady = (int) (clone $base)
                ->where('authority_purchase_status', 'Approved')
                ->whereNull('authority_purchase_released_to_purchaser_at')
                ->count();
        }

        return [
            self::item(
                'drafts',
                $drafts,
                $drafts.' unsubmitted '.Str::plural('ATP', $drafts),
                'Finish drafts and submit to the School Administrator.',
                self::safeRoute('supply-officer.atp.index', ['status' => 'Draft']),
                'file-pen',
                'amber'
            ),
            self::item(
                'release',
                $releaseReady,
                $releaseReady.' approved '.Str::plural('ATP', $releaseReady).' ready to release',
                'Send each approved ATP back to the Purchaser so they can request funding.',
                self::safeRoute('supply-officer.atp.index', ['status' => 'Approved']),
                'send',
                'blue'
            ),
        ];
    }

    /** @return list<array> */
    private static function schoolAdminRisItems(): array
    {
        $c = AdminAttentionSummary::counts();

        return [
            self::item(
                'pending',
                (int) $c['pendingRis'],
                $c['pendingRis'].' RIS waiting for your review',
                'Open RIS → Pending Accept, then decide on Ready to Sign.',
                self::safeRoute('school-admin.procurement-review', ['filter' => 'pending']),
                'clipboard-check',
                'amber'
            ),
            self::item(
                'cosign',
                (int) $c['awaitingCosign'],
                $c['awaitingCosign'].' RIS waiting for your signature',
                'Open RIS → Ready to Sign for Direct Approve, Pass to President, or Issued by.',
                self::safeRoute('school-admin.digital-signatures.sign-ris', ['filter' => 'for_decision']),
                'pen-line',
                'blue'
            ),
        ];
    }

    /** @return list<array> */
    private static function schoolAdminAtpItems(): array
    {
        if (! Schema::hasTable('authority_to_purchase_table')) {
            return [];
        }

        $query = DB::table('authority_to_purchase_table');
        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_assigned_reviewer_id')) {
            ReviewerAssignment::applyQueueFilter($query, 'authority_purchase_assigned_reviewer_id');
        }
        AccountingAttentionSummary::scopeAtpIncoming($query);
        $count = (int) $query->count();

        return [
            self::item(
                'pending',
                $count,
                $count.' ATP waiting for your approval',
                'Open Authority to Purchase and approve or return these documents.',
                self::safeRoute('school-admin.atp.index', ['status' => 'incoming']),
                'file-check-2',
                'amber'
            ),
        ];
    }

    /** @return list<array> */
    private static function schoolAdminRfcItems(): array
    {
        if (! Schema::hasTable('request_check_table')) {
            return [];
        }

        $query = DB::table('request_check_table');
        if (Schema::hasColumn('request_check_table', 'request_check_assigned_reviewer_id')) {
            ReviewerAssignment::applyQueueFilter($query, 'request_check_table.request_check_assigned_reviewer_id');
        }
        AccountingAttentionSummary::scopeRfcIncoming($query);
        $count = (int) $query->count();

        return [
            self::item(
                'pending',
                $count,
                $count.' funding '.Str::plural('request', $count).' waiting for your approval',
                'Open Check Approval Requests and approve or return these funding forms.',
                self::safeRoute('school-admin.rfc.index', ['status' => 'incoming']),
                'receipt-text',
                'amber'
            ),
        ];
    }

    /** @return list<array> */
    private static function schoolAdminDvItems(): array
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return [];
        }

        $count = (int) DB::table('disbursement_vouchers_table')
            ->where('dv_status', 'Pending')
            ->when(
                Schema::hasColumn('disbursement_vouchers_table', 'dv_is_archived'),
                fn ($q) => $q->where(function ($a) {
                    $a->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
                })
            )
            ->count();

        return [
            self::item(
                'pending',
                $count,
                $count.' disbursement '.Str::plural('voucher', $count).' waiting for your approval',
                'Open DV Requests and approve or return vouchers from Accounting.',
                self::safeRoute('school-admin.dv.index', ['status' => 'incoming']),
                'file-text',
                'amber'
            ),
        ];
    }

    /** @return list<array> */
    private static function accountingLiqItems(): array
    {
        $c = AccountingAttentionSummary::counts();

        return [
            self::item(
                'review',
                (int) $c['liqPending'],
                $c['liqPending'].' liquidation '.Str::plural('report', $c['liqPending']).' to review',
                'Submitted liquidations waiting for Accounting.',
                self::safeRoute('accounting.liq.index', ['status' => 'incoming', 'focus' => 'liq-review']),
                'receipt',
                'violet'
            ),
            self::item(
                'overdue',
                (int) $c['liqOverdue'],
                $c['liqOverdue'].' overdue '.Str::plural('liquidation', $c['liqOverdue']),
                'Past the submission deadline — prioritize these.',
                self::safeRoute('accounting.liq.index', ['status' => 'incoming', 'focus' => 'overdue']),
                'alarm-clock',
                'rose'
            ),
        ];
    }

    /** @return list<array> */
    private static function accountingDvItems(): array
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return [];
        }

        $drafts = (int) DB::table('disbursement_vouchers_table')
            ->whereIn('dv_status', ['Draft', 'Revision'])
            ->when(
                Schema::hasColumn('disbursement_vouchers_table', 'dv_is_archived'),
                fn ($q) => $q->where(function ($a) {
                    $a->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
                })
            )
            ->count();

        $readyRelease = (int) DB::table('disbursement_vouchers_table')
            ->where('dv_status', 'President Approved')
            ->when(
                Schema::hasColumn('disbursement_vouchers_table', 'dv_is_archived'),
                fn ($q) => $q->where(function ($a) {
                    $a->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
                })
            )
            ->count();

        return [
            self::item(
                'drafts',
                $drafts,
                $drafts.' DV draft'.($drafts === 1 ? '' : 's').' to finish',
                'Complete and submit vouchers to the School Administrator.',
                self::safeRoute('accounting.dv.index', ['status' => 'incoming']),
                'file-pen',
                'amber'
            ),
            self::item(
                'release',
                $readyRelease,
                $readyRelease.' voucher'.($readyRelease === 1 ? '' : 's').' ready to release the check',
                'President already approved — release the check and notify the Purchaser.',
                self::safeRoute('accounting.dv.index', ['status' => 'ready_release']),
                'circle-check',
                'emerald'
            ),
        ];
    }

    /** @return list<array> */
    private static function presidentRisItems(): array
    {
        $c = PresidentAttentionSummary::counts();

        return [
            self::item(
                'pending',
                (int) $c['pendingApprovalsCount'],
                $c['pendingApprovalsCount'].' RIS waiting for your approval',
                'Open Approvals and decide on these forwarded slips.',
                self::safeRoute('president.approvals', ['focus' => PresidentAttentionSummary::FOCUS_AWAITING_APPROVAL]),
                'file-check',
                'amber'
            ),
            self::item(
                'notify',
                (int) $c['awaitingNotifyCount'],
                $c['awaitingNotifyCount'].' approved RIS still need School Admin',
                'Tell the School Administrator to add their signature on these approved slips.',
                self::safeRoute('president.approvals', ['focus' => PresidentAttentionSummary::FOCUS_AWAITING_NOTIFY]),
                'bell',
                'blue'
            ),
        ];
    }

    /** @return list<array> */
    private static function presidentDvItems(): array
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return [];
        }

        $count = (int) DB::table('disbursement_vouchers_table')
            ->where('dv_status', 'With President')
            ->when(
                Schema::hasColumn('disbursement_vouchers_table', 'dv_is_archived'),
                fn ($q) => $q->where(function ($a) {
                    $a->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
                })
            )
            ->count();

        return [
            self::item(
                'pending',
                $count,
                $count.' disbursement '.Str::plural('voucher', $count).' waiting for your approval',
                'Open Disbursement Vouchers and approve or return these packages.',
                self::safeRoute('president.dv.index', ['status' => 'incoming']),
                'file-text',
                'amber'
            ),
        ];
    }

    /** @return list<array> */
    private static function receivingRrItems(): array
    {
        $c = ReceivingAttentionSummary::counts();

        return [
            self::item(
                'pending',
                (int) ($c['pendingCount'] ?? 0),
                ($c['pendingCount'] ?? 0).' receiving '.Str::plural('report', (int) ($c['pendingCount'] ?? 0)).' waiting for verification',
                'Open Receiving Reports and verify quantity, condition, and OR details.',
                self::safeRoute('receiving.rr.index', ['focus' => 'queue']),
                'package-check',
                'amber'
            ),
            self::item(
                'returned',
                (int) ($c['returnedCount'] ?? 0),
                ($c['returnedCount'] ?? 0).' returned receiving '.Str::plural('report', (int) ($c['returnedCount'] ?? 0)),
                'These were sent back — check if another review is needed.',
                self::safeRoute('receiving.rr.index', ['focus' => 'returned']),
                'rotate-ccw',
                'rose'
            ),
        ];
    }

    /** @return list<array> */
    private static function maintenanceReportItems(): array
    {
        $c = MaintenanceAttentionSummary::counts();
        $urgent = (int) ($c['urgentReportsNeedingAction'] ?? $c['attentionTotal'] ?? 0);

        return [
            self::item(
                'urgent',
                $urgent,
                $urgent.' urgent '.Str::plural('report', $urgent).' need action',
                'Open reports that still need maintenance attention.',
                url('/maintenance/reports/urgent'),
                'triangle-alert',
                'rose'
            ),
        ];
    }

    /** @return list<array> */
    private static function maintenanceScheduleItems(): array
    {
        if (! Schema::hasTable('maintenance_schedules_table')) {
            return [];
        }

        $overdue = (int) MaintenanceAttentionSummary::scopeOverdueSchedules(
            DB::table('maintenance_schedules_table')
        )->count();

        return [
            self::item(
                'overdue',
                $overdue,
                $overdue.' overdue maintenance '.Str::plural('schedule', $overdue),
                'Past the scheduled date — complete or reschedule these first.',
                SchedulePortal::schedulesUrl('?focus='.MaintenanceAttentionSummary::FOCUS_OVERDUE),
                'alarm-clock',
                'rose'
            ),
        ];
    }
}
