<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RIS → ATP → RFC → RR → LIQ stage snapshot used by Procurement Monitoring and the School Administrator dashboard.
 */
class AdminPipeline
{
    public const STAGE_ORDER = ['ris', 'atp', 'rfc', 'rr', 'liq'];

    /**
     * @return array<string, mixed>
     */
    public static function build(int $risId, bool $withLogs = false): array
    {
        try {
            return self::buildInner($risId, $withLogs);
        } catch (\Throwable $e) {
            return [
                'ris_id' => $risId,
                'stages' => [
                    'ris' => [
                        'exists' => true,
                        'key' => 'ris',
                        'type' => 'RIS',
                        'id' => $risId,
                        'label' => RisWorkflow::formNumber(null, $risId),
                        'hint' => 'Pipeline unavailable',
                        'view_url' => AdminPortal::route('operations.document', ['type' => 'ris', 'id' => $risId]),
                    ],
                    'atp' => ['exists' => false, 'key' => 'atp', 'type' => 'ATP', 'id' => null, 'label' => 'ATP', 'hint' => null, 'view_url' => null],
                    'rfc' => ['exists' => false, 'key' => 'rfc', 'type' => 'RFC', 'id' => null, 'label' => 'RFC', 'hint' => null, 'view_url' => null],
                    'rr' => ['exists' => false, 'key' => 'rr', 'type' => 'RR', 'id' => null, 'label' => 'RR', 'hint' => null, 'view_url' => null],
                    'liq' => ['exists' => false, 'key' => 'liq', 'type' => 'LIQ', 'id' => null, 'label' => 'LIQ', 'hint' => null, 'view_url' => null],
                ],
                'funds' => null,
                'payment_path' => null,
                'payment_path_label' => 'Not chosen',
                'current_stage' => 'ris',
                'current_hint' => 'Pipeline unavailable',
                'logs' => [],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildInner(int $risId, bool $withLogs = false): array
    {
        $chain = DocumentLineage::forRis($risId);
        $stageOrder = self::STAGE_ORDER;
        $stages = [];
        $paymentPath = null;
        $funds = null;

        foreach ($stageOrder as $key) {
            $node = $chain[$key] ?? null;
            if (! $node) {
                $stages[$key] = [
                    'exists' => false,
                    'key' => $key,
                    'type' => strtoupper($key),
                    'id' => null,
                    'label' => strtoupper($key),
                    'hint' => null,
                    'view_url' => null,
                ];

                continue;
            }

            $stages[$key] = [
                'exists' => true,
                'key' => $key,
                'type' => $node['type'] ?? strtoupper($key),
                'id' => $node['id'] ?? null,
                'label' => $node['label'] ?? strtoupper($key),
                'hint' => $node['hint'] ?? null,
                'view_url' => AdminPortal::route('operations.document', [
                    'type' => $key,
                    'id' => $node['id'],
                ]),
            ];
        }

        if (! empty($stages['rfc']['id']) && Schema::hasTable('request_check_table')) {
            $rfc = DB::table('request_check_table')->where('request_check_id', $stages['rfc']['id'])->first();
            if ($rfc) {
                if (! empty($rfc->request_check_funding_type)) {
                    $paymentPath = $rfc->request_check_funding_type;
                }
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
            $atp = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $stages['atp']['id'])
                ->first();
            $paymentPath = $atp->authority_purchase_payment_path ?? null;
        }

        $currentStage = 'ris';
        foreach ($stageOrder as $key) {
            if (! empty($stages[$key]['exists'])) {
                $currentStage = $key;
            }
        }

        $logs = [];
        if ($withLogs && Schema::hasTable('approval_logs_table')) {
            try {
                $refIds = collect($stages)
                    ->filter(fn ($s) => ! empty($s['exists']) && ! empty($s['id']))
                    ->mapWithKeys(fn ($s) => [strtoupper($s['key']) => (int) $s['id']]);

                $query = DB::table('approval_logs_table')
                    ->leftJoin('users_table', 'users_table.user_id', '=', 'approval_logs_table.approval_log_approved_by')
                    ->select(
                        'approval_logs_table.*',
                        'users_table.user_full_name as actor_name'
                    )
                    ->orderByDesc('approval_logs_table.approval_log_approved_at')
                    ->limit(40);

                $query->where(function ($builder) use ($refIds, $risId) {
                    $builder->where(function ($q) use ($risId) {
                        $q->where('approval_log_reference_type', 'RIS')
                            ->where('approval_log_reference_id', $risId);
                    });
                    foreach ($refIds as $type => $id) {
                        if ($type === 'RIS') {
                            continue;
                        }
                        $aliases = match ($type) {
                            'ATP' => ['ATP', 'authority_to_purchase'],
                            'RFC' => ['RFC', 'request_check', 'Request for Check'],
                            'RR' => ['RR', 'receiving_report', 'Receiving Report'],
                            'LIQ' => ['LIQ', 'liquidation', 'Liquidation Report'],
                            default => [$type],
                        };
                        $builder->orWhere(function ($q) use ($aliases, $id) {
                            $q->whereIn('approval_log_reference_type', $aliases)
                                ->where('approval_log_reference_id', $id);
                        });
                    }
                });

                $logs = $query->get()->map(function ($log) {
                    return [
                        'type' => $log->approval_log_reference_type,
                        'status' => $log->approval_log_approval_status,
                        'level' => $log->approval_log_level ?? null,
                        'remarks' => $log->approval_log_approval_remarks,
                        'actor' => $log->actor_name,
                        'at' => $log->approval_log_approved_at,
                    ];
                })->values()->all();
            } catch (\Throwable $e) {
                $logs = [];
            }
        }

        return [
            'ris_id' => $risId,
            'stages' => $stages,
            'funds' => $funds,
            'payment_path' => $paymentPath,
            'payment_path_label' => ProcurementPaymentPath::label($paymentPath),
            'current_stage' => $currentStage,
            'current_hint' => $stages[$currentStage]['hint'] ?? null,
            'logs' => $logs,
        ];
    }
}
