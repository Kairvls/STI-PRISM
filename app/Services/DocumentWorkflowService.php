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
     * Creator lists: pin In preparation (Draft) above everything else.
     * Call before unfinished / urgency / date sorts. Review queues should not use this.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public static function orderDraftFirst($query, string $statusColumn, string $draftStatus = 'Draft')
    {
        return $query->orderByRaw(
            "CASE WHEN {$statusColumn} = ? THEN 0 ELSE 1 END",
            [$draftStatus]
        );
    }

    /**
     * Creator lists: unfinished own work above in-flight records, with Draft (In preparation)
     * above revision statuses so urgency / date cannot bury drafts.
     * Call before date / urgency secondary sorts. Review queues should not use this.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  list<string>  $statuses
     */
    public static function orderOwnUnfinishedFirst($query, string $statusColumn, array $statuses = ['Draft', 'Minor Revision', 'President Revision'])
    {
        $statuses = array_values(array_filter($statuses, fn ($s) => is_string($s) && $s !== ''));
        if ($statuses === []) {
            return $query;
        }

        $draftStatus = 'Draft';
        $hasDraft = in_array($draftStatus, $statuses, true);
        $other = array_values(array_filter($statuses, fn ($s) => $s !== $draftStatus));

        if ($hasDraft && $other !== []) {
            $otherPlaceholders = implode(', ', array_fill(0, count($other), '?'));

            return $query->orderByRaw(
                "CASE
                    WHEN {$statusColumn} = ? THEN 0
                    WHEN {$statusColumn} IN ({$otherPlaceholders}) THEN 1
                    ELSE 2
                END",
                array_merge([$draftStatus], $other)
            );
        }

        $placeholders = implode(', ', array_fill(0, count($statuses), '?'));

        return $query->orderByRaw(
            "CASE WHEN {$statusColumn} IN ({$placeholders}) THEN 0 ELSE 1 END",
            $statuses
        );
    }

    /**
     * ATP soft-draft lists: In preparation (Draft or never submitted) first,
     * then revision, then the rest.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public static function orderSoftDraftFirst(
        $query,
        string $statusColumn,
        string $submittedAtColumn,
        string $revisionStatus = 'Minor Revision',
        string $draftStatus = 'Draft'
    ) {
        return $query->orderByRaw(
            "CASE
                WHEN {$statusColumn} = ? THEN 0
                WHEN {$submittedAtColumn} IS NULL THEN 0
                WHEN {$statusColumn} = ? THEN 1
                ELSE 2
            END",
            [$draftStatus, $revisionStatus]
        );
    }

    /**
     * When filtering by urgency (or similar), still keep In preparation rows in the list.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  callable(\Illuminate\Database\Query\Builder): void  $matchFilter
     */
    public static function whereFilterOrDraft(
        $query,
        string $statusColumn,
        callable $matchFilter,
        string $draftStatus = 'Draft'
    ) {
        return $query->where(function ($q) use ($statusColumn, $matchFilter, $draftStatus) {
            $matchFilter($q);
            $q->orWhere($statusColumn, $draftStatus);
        });
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
