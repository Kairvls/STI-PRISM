<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns a notification into a link that opens or filters the exact record it is about.
 * Older rows only stored a module URL (e.g. /purchaser/ris), so the record type and ID decide the destination.
 */
class NotificationLinks
{
    public static function openUrl(object $notification): string
    {
        return url('/notifications/'.(int) $notification->notification_id.'/open');
    }

    public static function resolve(object $notification): string
    {
        $stored = self::normalize((string) ($notification->notification_url ?? ''));
        $role = (string) ($notification->notification_target_role ?? '');
        $type = strtolower((string) ($notification->notification_reference_type ?? ''));
        $id = (int) ($notification->notification_reference_id ?? 0);

        if ($id <= 0) {
            return $stored;
        }

        $resolved = match ($type) {
            'ris' => self::ris($role, $stored, $id),
            'atp' => self::atp($role, $stored, $id),
            'rfc' => self::rfc($role, $stored, $id),
            'po' => self::po($role, $id),
            'liq' => self::liq($role, $id),
            'rr' => self::rr($role, $stored, $id),
            'proc' => self::replacement($role, $id),
            'borrowing_record' => self::maintenanceOrAdmin($role, $stored, 'borrowing', 'operations/borrowing', $id),
            'maintenance_schedule' => self::maintenanceOrAdmin($role, $stored, 'schedules', 'operations/schedules', $id),
            'equipment_transfer' => str_starts_with($stored, '/maintenance/')
                ? '/maintenance/equipment/transfer?record='.$id
                : null,
            'equipment' => str_contains($stored, 'replacement-suggestions')
                ? self::withQuery($stored, ['equipment' => $id])
                : null,
            default => null,
        };

        return $resolved ?: $stored;
    }

    private static function ris(string $role, string $stored, int $id): ?string
    {
        if ($role === WorkflowNotifier::ROLE_PURCHASER) {
            return str_contains($stored, 'view_ris=') ? $stored : '/purchaser/ris?view_ris='.$id;
        }

        if ($role === WorkflowNotifier::ROLE_PRESIDENT) {
            return str_contains($stored, 'direct-approvals')
                ? '/president/direct-approvals?ris='.$id
                : '/president/approvals?ris='.$id;
        }

        if (in_array($role, [WorkflowNotifier::ROLE_ADMIN, WorkflowNotifier::ROLE_SCHOOL_ADMIN], true)) {
            $prefix = $role === WorkflowNotifier::ROLE_SCHOOL_ADMIN ? '/school-admin' : '/admin';

            return str_contains($stored, 'sign-ris')
                ? $prefix.'/digital-signatures/sign-ris?ris='.$id
                : $prefix.'/procurement-review?ris='.$id;
        }

        return null;
    }

    private static function atp(string $role, string $stored, int $id): ?string
    {
        if ($role === WorkflowNotifier::ROLE_ACCOUNTING) {
            return '/accounting/authority-to-purchase/'.$id;
        }

        if ($role === WorkflowNotifier::ROLE_PURCHASER) {
            return str_contains($stored, 'selected_atp=') || str_contains($stored, 'view_atp=')
                ? $stored
                : '/purchaser/authority-to-purchase?view_atp='.$id;
        }

        return null;
    }

    private static function rfc(string $role, string $stored, int $id): ?string
    {
        if ($role === WorkflowNotifier::ROLE_ACCOUNTING) {
            return '/accounting/request-check/'.$id;
        }

        if ($role === WorkflowNotifier::ROLE_PURCHASER) {
            return str_contains($stored, 'receiving-reports')
                ? '/purchaser/receiving-reports?selected_rfc='.$id
                : '/purchaser/request-check?view_rfc='.$id;
        }

        return null;
    }

    private static function po(string $role, int $id): ?string
    {
        return match ($role) {
            WorkflowNotifier::ROLE_ACCOUNTING => '/accounting/purchase-orders/'.$id,
            WorkflowNotifier::ROLE_PURCHASER => '/purchaser/purchase-orders?view_po='.$id,
            default => null,
        };
    }

    private static function liq(string $role, int $id): ?string
    {
        return match ($role) {
            WorkflowNotifier::ROLE_ACCOUNTING => '/accounting/liquidation-reports/'.$id,
            WorkflowNotifier::ROLE_PURCHASER => '/purchaser/liquidation-reports?view_liq='.$id,
            default => null,
        };
    }

    private static function rr(string $role, string $stored, int $id): ?string
    {
        if ($role === WorkflowNotifier::ROLE_PURCHASER) {
            return str_contains($stored, 'back-orders')
                ? '/purchaser/back-orders?status=all&rr='.$id
                : '/purchaser/receiving-reports?view_rr='.$id;
        }

        if ($role === WorkflowNotifier::ROLE_RECEIVING) {
            return str_contains($stored, 'back-orders')
                ? '/receiving/back-orders?status=all&rr='.$id
                : '/receiving/reports?rr='.$id;
        }

        if ($role === WorkflowNotifier::ROLE_MAINTENANCE) {
            if (str_contains($stored, 'qr-tools')) {
                return '/maintenance/equipment/qr-tools?rr='.$id;
            }
            if (str_contains($stored, 'inventory')) {
                return '/maintenance/equipment/inventory?rr='.$id;
            }
        }

        return null;
    }

    private static function replacement(string $role, int $id): ?string
    {
        if ($role === WorkflowNotifier::ROLE_PURCHASER) {
            return '/purchaser/procurement/replacement-requests?record='.$id;
        }

        if ($role === WorkflowNotifier::ROLE_MAINTENANCE && Schema::hasTable('procurement_requests_table')) {
            try {
                $reportId = (int) DB::table('procurement_requests_table')
                    ->where('procurement_request_id', $id)
                    ->value('procurement_request_report_id');
            } catch (\Throwable $e) {
                $reportId = 0;
            }

            return $reportId > 0 ? '/maintenance/reports/details/'.$reportId : '/maintenance/reports/replacement';
        }

        return null;
    }

    private static function maintenanceOrAdmin(string $role, string $stored, string $maintenancePath, string $adminPath, int $id): ?string
    {
        if (in_array($role, [WorkflowNotifier::ROLE_ADMIN, WorkflowNotifier::ROLE_SCHOOL_ADMIN], true)) {
            $prefix = $role === WorkflowNotifier::ROLE_SCHOOL_ADMIN ? '/school-admin' : '/admin';

            return $prefix.'/'.$adminPath.'?record='.$id;
        }

        return str_starts_with($stored, '/maintenance/') || $role === WorkflowNotifier::ROLE_MAINTENANCE
            ? '/maintenance/'.$maintenancePath.'?record='.$id
            : null;
    }

    /**
     * Strips the scheme and host from absolute links saved by older code so they work on any domain.
     */
    private static function normalize(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $url)) {
            $path = (string) parse_url($url, PHP_URL_PATH);
            $query = (string) parse_url($url, PHP_URL_QUERY);
            $url = ($path !== '' ? $path : '/').($query !== '' ? '?'.$query : '');
        }

        return str_starts_with($url, '/') ? $url : '/'.$url;
    }

    private static function withQuery(string $url, array $params): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($params);
    }
}
