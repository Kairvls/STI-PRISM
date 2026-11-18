<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Chat share helpers: who is aligned with a procurement doc, and whether Approve is available.
 */
class ProcurementShareApproval
{
    /**
     * Role names that belong in the approval / verification flow for a document type.
     *
     * @return list<string>
     */
    public static function alignedRoleNames(string $type): array
    {
        $type = self::normalizeType($type);

        return match ($type) {
            'ris' => ['School Administrator', 'President', 'Administrator'],
            'atp', 'rfc' => ['School Administrator', 'Administrator'],
            'dv' => ['School Administrator', 'President', 'Accounting', 'Administrator'],
            'rr' => ['Receiving Officer', 'Administrator'],
            'liq' => ['Accounting', 'School Administrator', 'Administrator'],
            default => [],
        };
    }

    /**
     * @return list<int>
     */
    public static function alignedRoleIds(string $type): array
    {
        $type = self::normalizeType($type);

        return match ($type) {
            'ris' => [RoleAccess::SCHOOL_ADMIN, RoleAccess::PRESIDENT, RoleAccess::ADMIN],
            'atp', 'rfc' => [RoleAccess::SCHOOL_ADMIN, RoleAccess::ADMIN],
            'dv' => [RoleAccess::SCHOOL_ADMIN, RoleAccess::PRESIDENT, RoleAccess::ACCOUNTING, RoleAccess::ADMIN],
            'rr' => [RoleAccess::RECEIVING, RoleAccess::ADMIN],
            'liq' => [RoleAccess::ACCOUNTING, RoleAccess::SCHOOL_ADMIN, RoleAccess::ADMIN],
            default => [],
        };
    }

    public static function isAlignedRoleName(string $type, ?string $roleName): bool
    {
        $roleName = trim((string) $roleName);
        if ($roleName === '') {
            return false;
        }

        foreach (self::alignedRoleNames($type) as $aligned) {
            if (strcasecmp($aligned, $roleName) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fresh card + whether the current user can open Approve from chat.
     *
     * @return array{card: array, needs_approval: bool, can_approve: bool, approve_url: string|null, status: string|null}|null
     */
    public static function contextFor(string $type, int $id, ?object $user = null): ?array
    {
        $type = self::normalizeType($type);
        $card = RequestExplorer::shareCard($type, $id);
        if (! $card) {
            return null;
        }

        $user = $user ?: Auth::user();
        $status = (string) ($card['status'] ?? '');
        $needsApproval = self::needsApproval($type, $status, $id);
        $canApprove = $needsApproval && self::userCanApprove($type, $status, $user);
        $approveUrl = $canApprove ? self::approveUrl($type, $id, $status, $user) : null;

        return [
            'card' => $card,
            'needs_approval' => $needsApproval,
            'can_approve' => $canApprove && $approveUrl !== null,
            'approve_url' => $approveUrl,
            'status' => $status !== '' ? $status : null,
        ];
    }

    public static function needsApproval(string $type, string $status, int $id = 0): bool
    {
        $type = self::normalizeType($type);
        $status = trim($status);

        if ($status === '' || self::isTerminalStatus($type, $status)) {
            return false;
        }

        // Draft / in preparation: Open only — must be submitted before Approve.
        if (in_array($status, ['Draft', 'In preparation'], true)) {
            return false;
        }

        return match ($type) {
            'ris' => in_array($status, array_merge(
                RisWorkflow::incomingStatuses(),
                [RisWorkflow::ACCEPTED, RisWorkflow::FORWARDED, 'With President']
            ), true)
                || ($id > 0 && self::risNeedsIssuedBy($id)),
            'atp' => in_array($status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true),
            'rfc' => in_array($status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true),
            'dv' => in_array($status, ['Pending', 'Submitted', 'Under Review', 'With President'], true),
            'rr' => in_array($status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true),
            'liq' => in_array($status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending', 'Pending Admin Approval'], true),
            default => false,
        };
    }

    public static function userCanApprove(string $type, string $status, ?object $user = null): bool
    {
        $user = $user ?: Auth::user();
        if (! $user) {
            return false;
        }

        $roleIds = RoleAccess::roleIds($user);
        if ($roleIds === []) {
            return false;
        }

        $type = self::normalizeType($type);
        if (! RoleAccess::hasAnyRole(self::alignedRoleIds($type), $user)) {
            return false;
        }

        if ($type === 'ris' && in_array($status, [RisWorkflow::FORWARDED, 'With President'], true)) {
            return RoleAccess::hasAnyRole([RoleAccess::PRESIDENT, RoleAccess::ADMIN], $user);
        }

        if ($type === 'ris') {
            return RoleAccess::hasAnyRole([RoleAccess::SCHOOL_ADMIN, RoleAccess::ADMIN], $user);
        }

        if ($type === 'dv' && $status === 'With President') {
            return RoleAccess::hasAnyRole([RoleAccess::PRESIDENT, RoleAccess::ADMIN], $user);
        }

        return true;
    }

    public static function approveUrl(string $type, int $id, string $status, ?object $user = null): ?string
    {
        $type = self::normalizeType($type);
        $user = $user ?: Auth::user();
        if (! $user || $id < 1) {
            return null;
        }

        try {
            return match ($type) {
                'ris' => self::risApproveUrl($id, $status),
                'atp' => self::namedOrPath('school-admin.atp.index', '/school-admin/authority-to-purchase', ['view_atp' => $id]),
                'rfc' => self::namedOrPath('school-admin.rfc.index', '/school-admin/request-check', ['view_rfc' => $id]),
                'dv' => self::dvApproveUrl($id, $status, $user),
                'rr' => url('/receiving/reports?view_rr='.$id),
                'liq' => url('/accounting/liquidation-reports/'.$id),
                default => null,
            };
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function risApproveUrl(int $id, string $status): string
    {
        if (in_array($status, RisWorkflow::incomingStatuses(), true)) {
            return self::namedOrPath('school-admin.procurement-review', '/school-admin/procurement-review', ['view_ris' => $id]);
        }

        if ($status === RisWorkflow::ACCEPTED) {
            return self::namedOrPath(
                'school-admin.procurement-review.ris.direct-approve-form',
                '/school-admin/procurement-review/ris/'.$id.'/direct-approve-form'
            );
        }

        if (in_array($status, [RisWorkflow::FORWARDED, 'With President'], true)) {
            return self::namedOrPath(
                'president.approvals.ris.approve-form',
                '/president/approvals/ris/'.$id.'/approve'
            );
        }

        return self::namedOrPath(
            'school-admin.digital-signatures.sign-ris',
            '/school-admin/digital-signatures/sign-ris',
            ['filter' => 'for_decision', 'view_ris' => $id]
        );
    }

    private static function dvApproveUrl(int $id, string $status, object $user): string
    {
        if ($status === 'With President' && RoleAccess::hasAnyRole([RoleAccess::PRESIDENT, RoleAccess::ADMIN], $user)) {
            return url('/president/disbursement-vouchers?view_dv='.$id);
        }

        return self::namedOrPath('school-admin.dv.index', '/school-admin/disbursement-vouchers', ['view_dv' => $id]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private static function namedOrPath(string $routeName, string $path, array $query = []): string
    {
        if (Route::has($routeName)) {
            return route($routeName, $query);
        }

        $url = url($path);
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        return $url;
    }

    private static function isTerminalStatus(string $type, string $status): bool
    {
        $approved = [
            'Approved',
            RisWorkflow::DIRECTLY_APPROVED,
            RisWorkflow::PRESIDENT_APPROVED,
            'President Approved',
            'Rejected',
            'Final Rejected',
            RisWorkflow::PRESIDENT_REJECTED,
            RisWorkflow::PRESIDENT_REJECTED_LEGACY,
            'Cancelled',
            'Released',
            'Completed',
            'Verified',
            'Delivered',
        ];

        if (in_array($status, $approved, true)) {
            return true;
        }

        return $type === 'ris' && str_contains(strtolower($status), 'directly approved');
    }

    private static function risNeedsIssuedBy(int $id): bool
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return false;
        }

        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $id)->first();

        return $ris ? RisWorkflow::needsAdminIssuedBy($ris) : false;
    }

    private static function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return $type === 'lr' ? 'liq' : $type;
    }
}
