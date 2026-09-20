<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Submitter-picks-reviewer helpers for procurement workflow documents.
 */
class ReviewerAssignment
{
    /**
     * Document types the purchaser may reassign while still in review.
     *
     * @return array<string, array{
     *   table:string,
     *   id:string,
     *   column:string,
     *   role:string,
     *   ownership:string,
     *   label:string,
     *   ref_type:string,
     *   status_check:callable,
     *   notify_title:string,
     *   notify_type:string,
     *   notify_url:callable
     * }>
     */
    public static function reassignCatalog(): array
    {
        return [
            'atp' => [
                'table' => 'authority_to_purchase_table',
                'id' => 'authority_purchase_id',
                'column' => 'authority_purchase_assigned_reviewer_id',
                'role' => WorkflowNotifier::ROLE_ACCOUNTING,
                'ownership' => 'atp',
                'label' => 'Authority to Purchase',
                'ref_type' => 'ATP',
                'form_number' => 'authority_purchase_form_number',
                'notify_title' => 'ATP reassigned to you',
                'notify_type' => 'atp_reassigned',
                'notify_url' => fn (int $id) => '/accounting/authority-to-purchase/'.$id,
                'status_check' => function (object $row): bool {
                    return ($row->authority_purchase_status ?? null) === 'Pending'
                        && ! blank($row->authority_purchase_submitted_at ?? null)
                        && (int) ($row->authority_purchase_is_archived ?? 0) === 0;
                },
            ],
            'ris' => [
                'table' => 'requisition_issue_slip_table',
                'id' => 'ris_id',
                'column' => 'ris_assigned_reviewer_id',
                'role' => WorkflowNotifier::ROLE_ADMIN,
                'ownership' => 'ris',
                'label' => 'RIS',
                'ref_type' => 'RIS',
                'form_number' => 'ris_form_number',
                'notify_title' => 'RIS reassigned to you',
                'notify_type' => 'ris_reassigned',
                'notify_url' => fn (int $id) => '/admin/procurement-review',
                'status_check' => function (object $row): bool {
                    return in_array($row->ris_status ?? null, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true)
                        && (int) ($row->ris_is_archived ?? 0) === 0;
                },
            ],
            'rfc' => [
                'table' => 'request_check_table',
                'id' => 'request_check_id',
                'column' => 'request_check_assigned_reviewer_id',
                'role' => WorkflowNotifier::ROLE_ACCOUNTING,
                'ownership' => 'rfc',
                'label' => 'Request for Check',
                'ref_type' => 'RFC',
                'form_number' => 'request_check_form_number',
                'notify_title' => 'RFC reassigned to you',
                'notify_type' => 'rfc_reassigned',
                'notify_url' => fn (int $id) => '/accounting/request-check/'.$id,
                'status_check' => function (object $row): bool {
                    return in_array($row->request_check_status ?? null, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true)
                        && (int) ($row->request_check_is_archived ?? 0) === 0;
                },
            ],
            'rr' => [
                'table' => 'receiving_reports_table',
                'id' => 'receiving_report_id',
                'column' => 'receiving_report_assigned_reviewer_id',
                'role' => WorkflowNotifier::ROLE_RECEIVING,
                'ownership' => 'rr',
                'label' => 'Receiving Report',
                'ref_type' => 'RR',
                'form_number' => 'receiving_report_form_number',
                'notify_title' => 'RR reassigned to you',
                'notify_type' => 'rr_reassigned',
                'notify_url' => fn (int $id) => '/receiving/reports',
                'status_check' => function (object $row): bool {
                    return in_array($row->receiving_report_status ?? null, ['Submitted', 'Under Review', 'Resubmitted'], true)
                        && (int) ($row->receiving_report_is_archived ?? 0) === 0;
                },
            ],
            'liq' => [
                'table' => 'liquidation_reports_table',
                'id' => 'liquidation_report_id',
                'column' => 'liquidation_report_assigned_reviewer_id',
                'role' => WorkflowNotifier::ROLE_ACCOUNTING,
                'ownership' => 'liq',
                'label' => 'Liquidation Report',
                'ref_type' => 'LIQ',
                'form_number' => 'liquidation_report_form_number',
                'notify_title' => 'Liquidation reassigned to you',
                'notify_type' => 'liq_reassigned',
                'notify_url' => fn (int $id) => '/accounting/liquidation-reports/'.$id,
                'status_check' => function (object $row): bool {
                    return in_array($row->liquidation_report_status ?? null, ['Submitted', 'Under Review', 'Resubmitted', 'Pending Admin Approval'], true)
                        && (int) ($row->liquidation_report_is_archived ?? 0) === 0;
                },
            ],
            'po' => [
                'table' => 'purchase_orders_table',
                'id' => 'purchase_order_id',
                'column' => 'purchase_order_assigned_reviewer_id',
                'role' => WorkflowNotifier::ROLE_ACCOUNTING,
                'ownership' => 'po',
                'label' => 'Purchase Order',
                'ref_type' => 'PO',
                'form_number' => 'purchase_order_number',
                'notify_title' => 'Purchase Order reassigned to you',
                'notify_type' => 'po_reassigned',
                'notify_url' => fn (int $id) => '/accounting/purchase-orders?status=incoming',
                'status_check' => function (object $row): bool {
                    return ($row->purchase_order_status ?? null) === 'Submitted'
                        && (int) ($row->purchase_order_is_archived ?? 0) === 0;
                },
            ],
        ];
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public static function options(string $role): array
    {
        $ids = WorkflowNotifier::userIdsForRole($role);
        if ($ids === [] || ! Schema::hasTable('users_table')) {
            return [];
        }

        try {
            $rows = DB::table('users_table')
                ->whereIn('user_id', $ids)
                ->orderBy('user_full_name')
                ->get(['user_id', 'user_full_name', 'user_username']);
        } catch (\Throwable $e) {
            return [];
        }

        return $rows->map(function ($row) {
            $name = trim((string) ($row->user_full_name ?: $row->user_username ?: ('User #'.$row->user_id)));

            return [
                'id' => (int) $row->user_id,
                'name' => $name,
            ];
        })->values()->all();
    }

    public static function resolve(Request $request, string $role, string $input = 'assigned_reviewer_id'): int
    {
        $raw = $request->input($input);
        $id = (int) $raw;
        $allowed = WorkflowNotifier::userIdsForRole($role);

        if ($id <= 0 || ! in_array($id, $allowed, true)) {
            $label = match ($role) {
                WorkflowNotifier::ROLE_ADMIN => 'Administrator',
                WorkflowNotifier::ROLE_ACCOUNTING => 'Accounting',
                WorkflowNotifier::ROLE_RECEIVING => 'Receiving Officer',
                default => 'reviewer',
            };

            throw ValidationException::withMessages([
                $input => "Please select a {$label} reviewer before submitting.",
            ]);
        }

        return $id;
    }

    public static function canOverride(): bool
    {
        return RoleAccess::isAdmin();
    }

    public static function isAssignedToCurrentUser(?int $assignedReviewerId): bool
    {
        if (self::canOverride()) {
            return true;
        }

        $assigned = (int) ($assignedReviewerId ?? 0);
        if ($assigned <= 0) {
            // Legacy rows without an assignee stay visible to the whole role.
            return true;
        }

        return $assigned === (int) Auth::id();
    }

    /**
     * Restrict a queue query to the current user (plus legacy nulls).
     * Administrators bypass the filter (Admin override).
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public static function applyQueueFilter($query, string $qualifiedColumn): void
    {
        if (self::canOverride()) {
            return;
        }

        $userId = (int) Auth::id();
        $query->where(function ($q) use ($qualifiedColumn, $userId) {
            $q->where($qualifiedColumn, $userId)
                ->orWhereNull($qualifiedColumn);
        });
    }

    public static function assertCanAct(?int $assignedReviewerId, string $documentLabel = 'document'): void
    {
        if (self::isAssignedToCurrentUser($assignedReviewerId)) {
            return;
        }

        abort(403, 'This '.$documentLabel.' is assigned to another reviewer.');
    }

    /**
     * Purchaser-only reassignment while the document is still in review.
     *
     * @return array{ok:bool,message:string}
     */
    public static function reassignForPurchaser(string $type, int $id, int $newReviewerId): array
    {
        $catalog = self::reassignCatalog();
        if (! isset($catalog[$type])) {
            return ['ok' => false, 'message' => 'Unknown document type.'];
        }

        $cfg = $catalog[$type];
        if (! Schema::hasTable($cfg['table']) || ! Schema::hasColumn($cfg['table'], $cfg['column'])) {
            return ['ok' => false, 'message' => 'Reviewer assignment is not available for this document.'];
        }

        $row = DB::table($cfg['table'])->where($cfg['id'], $id)->first();
        if (! $row) {
            return ['ok' => false, 'message' => $cfg['label'].' not found.'];
        }

        PurchaserDocumentAccess::assertOwns($row, $cfg['ownership']);

        if (! ($cfg['status_check'])($row)) {
            return ['ok' => false, 'message' => 'Only documents waiting for review can change reviewer.'];
        }

        $allowed = WorkflowNotifier::userIdsForRole($cfg['role']);
        if (! in_array($newReviewerId, $allowed, true)) {
            throw ValidationException::withMessages([
                'assigned_reviewer_id' => 'Please select a valid reviewer.',
            ]);
        }

        $oldId = (int) ($row->{$cfg['column']} ?? 0);
        if ($oldId === $newReviewerId) {
            return ['ok' => true, 'message' => 'Reviewer is already assigned.'];
        }

        $updatedAt = match ($type) {
            'atp' => 'authority_purchase_updated_at',
            'ris' => 'ris_updated_at',
            'rfc' => 'request_check_updated_at',
            'rr' => 'receiving_report_updated_at',
            'liq' => 'liquidation_report_updated_at',
            'po' => 'purchase_order_updated_at',
            default => null,
        };

        $update = [$cfg['column'] => $newReviewerId];
        if ($updatedAt && Schema::hasColumn($cfg['table'], $updatedAt)) {
            $update[$updatedAt] = now();
        }

        DB::table($cfg['table'])->where($cfg['id'], $id)->update($update);

        // Keep linked ATPs aligned when reassigning a PO.
        if ($type === 'po' && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_assigned_reviewer_id')) {
            $atpIds = PurchaseOrderBasket::atpIdsForPo($id);
            if ($atpIds !== []) {
                DB::table('authority_to_purchase_table')
                    ->whereIn('authority_purchase_id', $atpIds)
                    ->update([
                        'authority_purchase_assigned_reviewer_id' => $newReviewerId,
                        'authority_purchase_updated_at' => now(),
                    ]);
            }
        }

        $formField = $cfg['form_number'];
        $ref = trim((string) ($row->{$formField} ?? '')) ?: ($cfg['ref_type'].' #'.$id);

        WorkflowNotifier::toUser(
            $newReviewerId,
            $cfg['role'],
            $cfg['notify_title'],
            $ref.' was reassigned to you for review.',
            $cfg['notify_type'],
            $cfg['ref_type'],
            $id,
            ($cfg['notify_url'])($id)
        );

        if ($oldId > 0 && $oldId !== $newReviewerId) {
            WorkflowNotifier::toUser(
                $oldId,
                $cfg['role'],
                $cfg['label'].' reassigned',
                $ref.' was reassigned to another reviewer.',
                $cfg['notify_type'].'_removed',
                $cfg['ref_type'],
                $id,
                ($cfg['notify_url'])($id)
            );
        }

        return ['ok' => true, 'message' => 'Reviewer updated.'];
    }
}
