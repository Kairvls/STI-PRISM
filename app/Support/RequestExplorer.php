<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Request No. (PR-YYYYMM-####) explorer: resolve the ticket and build the full pipeline
 * RIS → ATP → RFC → DV → RR → LIQ with role-aware open links.
 */
class RequestExplorer
{
    public const STAGE_ORDER = ['ris', 'atp', 'rfc', 'dv', 'rr', 'liq'];

    public const STAGE_LABELS = [
        'ris' => 'Canvas + RIS',
        'atp' => 'Authority to Purchase',
        'rfc' => 'Request for Check',
        'dv' => 'Disbursement Voucher',
        'rr' => 'Receiving Report',
        'liq' => 'Liquidation Report',
    ];

    /**
     * Normalize typed / URL request numbers to PR-YYYYMM-#### (or legacy PR-YYYY-####).
     */
    public static function normalize(string $requestNo): string
    {
        $raw = trim(urldecode($requestNo));
        $raw = strtoupper(str_replace(' ', '', $raw));

        if (preg_match('/^PR-(\d{6})-(\d{1,6})$/', $raw, $m)) {
            return 'PR-'.$m[1].'-'.str_pad($m[2], 4, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^PR-(\d{4})-(\d{1,6})$/', $raw, $m)) {
            return 'PR-'.$m[1].'-'.str_pad($m[2], 4, '0', STR_PAD_LEFT);
        }

        return $raw;
    }

    /**
     * @return array{
     *   request_no: string,
     *   ris_id: int|null,
     *   stages: array<string, array<string, mixed>>,
     *   current_stage: string,
     *   current_hint: string|null,
     *   payment_path: string|null,
     *   payment_path_label: string,
     *   funds: array<string, mixed>|null,
     *   atp_siblings: array<int, array<string, mixed>>
     * }|null
     */
    public static function find(string $requestNo): ?array
    {
        $requestNo = self::normalize($requestNo);
        if ($requestNo === '') {
            return null;
        }

        $risId = self::resolveRisId($requestNo);
        if (! $risId) {
            return null;
        }

        $chain = DocumentLineage::forRis($risId);
        $stages = [];

        foreach (self::STAGE_ORDER as $key) {
            if ($key === 'dv') {
                $stages[$key] = self::dvStageFromChain($chain);
                continue;
            }

            $node = $chain[$key] ?? null;
            if (! $node) {
                $stages[$key] = self::emptyStage($key);
                continue;
            }

            $stages[$key] = [
                'exists' => true,
                'key' => $key,
                'type' => $node['type'] ?? strtoupper($key),
                'id' => (int) ($node['id'] ?? 0) ?: null,
                'label' => $node['label'] ?? strtoupper($key),
                'hint' => $node['hint'] ?? null,
                'view_url' => self::viewUrlFor($key, (int) ($node['id'] ?? 0)),
            ];
        }

        // Prefer a DV open link even when lineage purchaser URLs differ by portal.
        if (! empty($stages['rfc']['id']) && empty($stages['dv']['exists'])) {
            $stages['dv'] = self::dvStageFromRfc((int) $stages['rfc']['id']);
        }

        $paymentPath = null;
        $funds = null;
        if (! empty($stages['rfc']['id']) && Schema::hasTable('request_check_table')) {
            $rfc = DB::table('request_check_table')->where('request_check_id', $stages['rfc']['id'])->first();
            if ($rfc) {
                $paymentPath = $rfc->request_check_funding_type ?? null;
                $released = ! empty($rfc->request_check_funds_released_at);
                $funds = [
                    'exists' => true,
                    'label' => $released ? 'Funds released' : 'Funds pending',
                    'status' => $released ? 'Released' : 'Pending',
                    'released_at' => $rfc->request_check_funds_released_at ?? null,
                ];
            }
        }
        if (! $paymentPath && ! empty($stages['atp']['id']) && Schema::hasTable('authority_to_purchase_table')) {
            $paymentPath = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $stages['atp']['id'])
                ->value('authority_purchase_payment_path');
        }

        $visibleStages = self::visibleStageKeys($stages, $paymentPath);

        $currentStage = 'ris';
        foreach ($visibleStages as $key) {
            if (! empty($stages[$key]['exists'])) {
                $currentStage = $key;
            }
        }

        $siblings = collect($chain['atp_siblings'] ?? [])
            ->map(fn ($node) => [
                'id' => (int) ($node['id'] ?? 0) ?: null,
                'label' => $node['label'] ?? 'ATP',
                'hint' => $node['hint'] ?? null,
                'view_url' => self::viewUrlFor('atp', (int) ($node['id'] ?? 0)),
            ])
            ->filter(fn ($row) => ! empty($row['id']))
            ->values()
            ->all();

        return [
            'request_no' => $requestNo,
            'ris_id' => $risId,
            'stages' => $stages,
            'stage_order' => $visibleStages,
            'current_stage' => $currentStage,
            'current_hint' => $stages[$currentStage]['hint'] ?? null,
            'payment_path' => $paymentPath,
            'payment_path_label' => class_exists(ProcurementPaymentPath::class)
                ? ProcurementPaymentPath::label($paymentPath)
                : (string) ($paymentPath ?: 'Not chosen'),
            'funds' => $funds,
            'atp_siblings' => $siblings,
        ];
    }

    /**
     * Stages shown in the explorer for this payment path.
     * Direct Payment / Cash Installment end at RR (no LIQ).
     * Cash Advance ends at Liquidation Report.
     *
     * @param  array<string, array<string, mixed>>  $stages
     * @return list<string>
     */
    public static function visibleStageKeys(array $stages, ?string $paymentPath): array
    {
        $hasLiq = ! empty($stages['liq']['exists']);
        $isCashAdvance = ProcurementPaymentPath::requiresLiquidation($paymentPath);
        // Direct Payment / Cash Installment (and legacy RFC) stop at RR — hide pending LIQ.
        $hideLiq = ! $hasLiq && (
            ProcurementPaymentPath::endsAtReceivingReport($paymentPath)
            || (! $isCashAdvance && ! empty($stages['rfc']['exists']))
        );

        return array_values(array_filter(self::STAGE_ORDER, function (string $key) use ($hideLiq) {
            return ! ($key === 'liq' && $hideLiq);
        }));
    }

    public static function resolveRisId(string $requestNo): ?int
    {
        $requestNo = self::normalize($requestNo);
        if ($requestNo === '') {
            return null;
        }

        if (Schema::hasTable('requisition_issue_slip_table')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
            $risId = (int) DB::table('requisition_issue_slip_table')
                ->where('ris_request_no', $requestNo)
                ->value('ris_id');
            if ($risId > 0) {
                return $risId;
            }
        }

        if (Schema::hasTable('authority_to_purchase_table')
            && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_request_no')) {
            $risId = (int) DB::table('authority_to_purchase_table')
                ->where('authority_purchase_request_no', $requestNo)
                ->value('authority_purchase_ris_id');
            if ($risId > 0) {
                return $risId;
            }
        }

        if (Schema::hasTable('request_check_table')
            && Schema::hasColumn('request_check_table', 'request_check_request_no')) {
            $rfc = DB::table('request_check_table')
                ->where('request_check_request_no', $requestNo)
                ->first(['request_check_id', 'request_check_authority_purchase_id']);
            if ($rfc) {
                $atpId = (int) ($rfc->request_check_authority_purchase_id ?? 0);
                if ($atpId < 1 && class_exists(RfcAtpLinks::class) && RfcAtpLinks::tableExists()) {
                    $ids = RfcAtpLinks::atpIdsFor((int) $rfc->request_check_id, null);
                    $atpId = (int) ($ids[0] ?? 0);
                }
                if ($atpId > 0 && Schema::hasTable('authority_to_purchase_table')) {
                    $risId = (int) DB::table('authority_to_purchase_table')
                        ->where('authority_purchase_id', $atpId)
                        ->value('authority_purchase_ris_id');
                    if ($risId > 0) {
                        return $risId;
                    }
                }
            }
        }

        if (Schema::hasTable('disbursement_vouchers_table')
            && Schema::hasColumn('disbursement_vouchers_table', 'dv_request_no')) {
            $rfcId = (int) DB::table('disbursement_vouchers_table')
                ->where('dv_request_no', $requestNo)
                ->value('dv_request_check_id');
            if ($rfcId > 0) {
                $atpId = (int) DB::table('request_check_table')
                    ->where('request_check_id', $rfcId)
                    ->value('request_check_authority_purchase_id');
                if ($atpId > 0) {
                    $risId = (int) DB::table('authority_to_purchase_table')
                        ->where('authority_purchase_id', $atpId)
                        ->value('authority_purchase_ris_id');
                    if ($risId > 0) {
                        return $risId;
                    }
                }
            }
        }

        if (Schema::hasTable('receiving_reports_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_request_no')) {
            $rfcId = (int) DB::table('receiving_reports_table')
                ->where('receiving_report_request_no', $requestNo)
                ->value('receiving_report_request_check_id');
            if ($rfcId > 0) {
                $atpId = (int) DB::table('request_check_table')
                    ->where('request_check_id', $rfcId)
                    ->value('request_check_authority_purchase_id');
                if ($atpId > 0) {
                    $risId = (int) DB::table('authority_to_purchase_table')
                        ->where('authority_purchase_id', $atpId)
                        ->value('authority_purchase_ris_id');
                    if ($risId > 0) {
                        return $risId;
                    }
                }
            }
        }

        if (Schema::hasTable('liquidation_reports_table')
            && Schema::hasColumn('liquidation_reports_table', 'liquidation_report_request_no')
            && Schema::hasColumn('liquidation_reports_table', 'liquidation_report_receiving_report_id')) {
            $rrId = (int) DB::table('liquidation_reports_table')
                ->where('liquidation_report_request_no', $requestNo)
                ->value('liquidation_report_receiving_report_id');
            if ($rrId > 0 && Schema::hasTable('receiving_reports_table')) {
                $rfcId = (int) DB::table('receiving_reports_table')
                    ->where('receiving_report_id', $rrId)
                    ->value('receiving_report_request_check_id');
                if ($rfcId > 0) {
                    $atpId = (int) DB::table('request_check_table')
                        ->where('request_check_id', $rfcId)
                        ->value('request_check_authority_purchase_id');
                    if ($atpId > 0) {
                        $risId = (int) DB::table('authority_to_purchase_table')
                            ->where('authority_purchase_id', $atpId)
                            ->value('authority_purchase_ris_id');
                        if ($risId > 0) {
                            return $risId;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyStage(string $key): array
    {
        return [
            'exists' => false,
            'key' => $key,
            'type' => strtoupper($key),
            'id' => null,
            'label' => self::STAGE_LABELS[$key] ?? strtoupper($key),
            'hint' => null,
            'view_url' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $chain
     * @return array<string, mixed>
     */
    private static function dvStageFromChain(array $chain): array
    {
        $rfcId = isset($chain['rfc']['id']) ? (int) $chain['rfc']['id'] : 0;

        return $rfcId > 0 ? self::dvStageFromRfc($rfcId) : self::emptyStage('dv');
    }

    /**
     * @return array<string, mixed>
     */
    private static function dvStageFromRfc(int $rfcId): array
    {
        if ($rfcId < 1 || ! Schema::hasTable('disbursement_vouchers_table')) {
            return self::emptyStage('dv');
        }

        $dv = DB::table('disbursement_vouchers_table')
            ->where('dv_request_check_id', $rfcId)
            ->orderByDesc('dv_id')
            ->first();

        if (! $dv) {
            return self::emptyStage('dv');
        }

        $status = (string) ($dv->dv_status ?? 'Draft');

        return [
            'exists' => true,
            'key' => 'dv',
            'type' => 'DV',
            'id' => (int) $dv->dv_id,
            'label' => $dv->dv_form_number ?: ('DV #'.$dv->dv_id),
            'hint' => $status,
            'view_url' => self::viewUrlFor('dv', (int) $dv->dv_id),
        ];
    }

    private static function viewUrlFor(string $key, int $id): ?string
    {
        if ($id < 1) {
            return null;
        }

        $portal = self::viewerPortal();

        if (in_array($portal, ['admin', 'school-admin'], true)
            && in_array($key, ['ris', 'atp', 'rfc', 'rr', 'liq'], true)
            && Route::has($portal.'.operations.document')) {
            try {
                return route($portal.'.operations.document', ['type' => $key, 'id' => $id]);
            } catch (\Throwable) {
                // fall through
            }
        }

        $candidates = match ($key) {
            'ris' => [
                ['purchaser.ris.index', ['view_ris' => $id]],
                ['supply-officer.ris.index', ['view_ris' => $id]],
            ],
            'atp' => [
                ['supply-officer.atp.index', ['view_atp' => $id]],
                ['purchaser.atp.index', ['view_atp' => $id]],
            ],
            'rfc' => [
                ['purchaser.rfc.index', ['view_rfc' => $id]],
            ],
            'dv' => [
                ['accounting.dv.show', ['id' => $id]],
                ['accounting.dv.index', ['view_dv' => $id]],
                ['president.dv.show', ['id' => $id]],
                ['school-admin.dv.show', ['id' => $id]],
            ],
            'rr' => [
                ['purchaser.rr.index', ['view_rr' => $id]],
            ],
            'liq' => [
                ['purchaser.liq.index', ['view_liq' => $id]],
                ['accounting.liq.index', ['view_liq' => $id]],
            ],
            default => [],
        };

        // Prefer routes for the active portal when namespaced that way.
        usort($candidates, function ($a, $b) use ($portal) {
            $aScore = str_starts_with($a[0], $portal.'.') ? 0 : 1;
            $bScore = str_starts_with($b[0], $portal.'.') ? 0 : 1;

            return $aScore <=> $bScore;
        });

        foreach ($candidates as [$name, $params]) {
            if (! Route::has($name)) {
                continue;
            }
            try {
                return route($name, $params);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private static function viewerPortal(): string
    {
        if (RoleAccess::adminShell()) {
            return 'admin';
        }

        $fromPath = self::portalKeyFromPath(request()->path());
        if ($fromPath) {
            return $fromPath;
        }

        if (request()->hasSession()) {
            $remembered = request()->session()->get(RoleAccess::PORTAL_SESSION_KEY);
            if (is_string($remembered) && $remembered !== '') {
                return $remembered;
            }
        }

        $referer = (string) request()->headers->get('referer', '');
        if ($referer !== '') {
            $path = parse_url($referer, PHP_URL_PATH);
            $fromReferer = self::portalKeyFromPath(is_string($path) ? ltrim($path, '/') : '');
            if ($fromReferer) {
                return $fromReferer;
            }
        }

        $primary = RoleAccess::primaryRoleId();

        return RoleAccess::portalMeta()[$primary]['key'] ?? 'purchaser';
    }

    private static function portalKeyFromPath(string $path): ?string
    {
        $path = trim($path, '/');
        foreach (RoleAccess::portalMeta() as $portal) {
            $match = $portal['match'];
            if ($path === $match || str_starts_with($path, $match.'/')) {
                return $portal['key'];
            }
        }

        return null;
    }

    /**
     * Request page inside the portal the person is using now.
     * School Administrator stays on /school-admin/requests/…, Purchaser on /purchaser/requests/…, and so on.
     */
    public static function showUrl(string $requestNo, ?string $portal = null): ?string
    {
        $normalized = self::normalize($requestNo);
        if ($normalized === '') {
            return null;
        }

        $sessionPortal = request()->hasSession()
            ? request()->session()->get(RoleAccess::PORTAL_SESSION_KEY)
            : null;
        $primary = RoleAccess::portalMeta()[RoleAccess::primaryRoleId()]['key'] ?? null;

        $candidates = array_values(array_unique(array_filter([
            $portal,
            $portal === null ? self::viewerPortal() : null,
            is_string($sessionPortal) ? $sessionPortal : null,
            $primary,
        ])));

        foreach ($candidates as $key) {
            $name = $key.'.requests.show';
            if (! Route::has($name)) {
                continue;
            }

            try {
                return route($name, ['requestNo' => $normalized]);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    public static function layoutForPortal(?string $portal = null): string
    {
        $portal = $portal ?: self::viewerPortal();

        return match ($portal) {
            'admin', 'school-admin' => 'layouts.admin-layout',
            'accounting' => 'layouts.accounting-layout',
            'president' => 'layouts.president-layout',
            'supply-officer' => 'layouts.supply-officer-layout',
            'receiving' => 'layouts.receiving-layout',
            'maintenance' => 'layouts.maintenance-layout',
            default => 'layouts.purchaser-layout',
        };
    }
}
