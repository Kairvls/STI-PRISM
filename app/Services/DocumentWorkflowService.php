<?php

namespace App\Services;

use App\Support\WorkflowNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared draft / archive / notify helpers for purchaser workflow documents.
 */
class DocumentWorkflowService
{
    /**
     * Records in an editable status that are not archived may be mutated.
     */
    public static function isEditable(object $record, string $statusField, array $editableStatuses, string $archivedField): bool
    {
        return in_array($record->{$statusField} ?? null, $editableStatuses, true)
            && empty($record->{$archivedField} ?? null);
    }

    /**
     * ATP-style soft draft: pending status, never submitted, not archived.
     */
    public static function isSoftDraft(
        object $record,
        string $statusField,
        string $submittedAtField,
        string $archivedField,
        string $pendingStatus = 'Pending'
    ): bool {
        return ($record->{$statusField} ?? null) === $pendingStatus
            && blank($record->{$submittedAtField} ?? null)
            && (int) ($record->{$archivedField} ?? 0) === 0;
    }

    /**
     * Scope a list query to active or archived rows.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public static function applyArchiveFilter($query, string $qualifiedColumn, bool $archiveView): void
    {
        if ($archiveView) {
            $query->where($qualifiedColumn, 1);

            return;
        }

        $query->where(function ($q) use ($qualifiedColumn) {
            $q->whereNull($qualifiedColumn)
                ->orWhere($qualifiedColumn, 0);
        });
    }

    /**
     * Creator lists: put unfinished own work (Draft / Minor Revision) above in-flight records.
     * Call before date / urgency secondary sorts. Review queues should not use this.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  list<string>  $statuses
     */
    public static function orderOwnUnfinishedFirst($query, string $statusColumn, array $statuses = ['Draft', 'Minor Revision'])
    {
        $statuses = array_values(array_filter($statuses, fn ($s) => is_string($s) && $s !== ''));
        if ($statuses === []) {
            return $query;
        }

        $placeholders = implode(', ', array_fill(0, count($statuses), '?'));

        return $query->orderByRaw(
            "CASE WHEN {$statusColumn} IN ({$placeholders}) THEN 0 ELSE 1 END",
            $statuses
        );
    }

    /**
     * ATP soft-draft lists: unsubmitted + Minor Revision first (Pending without submitted_at).
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public static function orderSoftDraftFirst(
        $query,
        string $statusColumn,
        string $submittedAtColumn,
        string $revisionStatus = 'Minor Revision'
    ) {
        return $query->orderByRaw(
            "CASE
                WHEN {$statusColumn} = ? THEN 0
                WHEN {$submittedAtColumn} IS NULL THEN 0
                ELSE 1
            END",
            [$revisionStatus]
        );
    }

    /**
     * Toggle the archived flag on a workflow document row.
     */
    public static function setArchived(
        string $table,
        string $idColumn,
        $id,
        string $archivedColumn,
        string $updatedAtColumn,
        bool $archived
    ): void {
        DB::table($table)
            ->where($idColumn, $id)
            ->update([
                $archivedColumn => $archived ? 1 : 0,
                $updatedAtColumn => now(),
            ]);
    }

    /**
     * Notify a specific reviewer (preferred) or the whole role (legacy fallback).
     */
    public static function notifySubmitted(
        string $role,
        string $title,
        string $message,
        string $type,
        string $refType,
        int $refId,
        string $url,
        ?int $reviewerId = null
    ): void {
        if ($reviewerId && $reviewerId > 0) {
            WorkflowNotifier::toUser($reviewerId, $role, $title, $message, $type, $refType, $refId, $url);

            return;
        }

        WorkflowNotifier::toRole($role, $title, $message, $type, $refType, $refId, $url);
    }

    /**
     * Whether a column exists on a table (safe when schemas differ across envs).
     */
    public static function hasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }
}
