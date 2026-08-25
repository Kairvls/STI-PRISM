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
     * Notify a role that a document was submitted for review.
     */
    public static function notifySubmitted(
        string $role,
        string $title,
        string $message,
        string $type,
        string $refType,
        int $refId,
        string $url
    ): void {
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
