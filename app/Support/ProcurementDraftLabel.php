<?php

namespace App\Support;

/**
 * Display labels for unsubmitted procurement records.
 * DB / filter / save_action values stay "Draft"; user-facing copy uses these helpers.
 */
class ProcurementDraftLabel
{
    public const STATUS = 'In preparation';

    public const STATUS_LONG = 'In preparation — not submitted yet';

    public const SAVE = 'Save for later';

    public const SAVE_SHORT = 'Save';

    public static function isDraftStatus(?string $status): bool
    {
        $status = trim((string) $status);

        return $status === '' || strcasecmp($status, 'Draft') === 0;
    }

    /**
     * Map a stored status (or any label) for display.
     */
    public static function status(?string $status): string
    {
        $status = trim((string) $status);
        if (self::isDraftStatus($status)) {
            return self::STATUS;
        }

        if (strcasecmp($status, 'Draft — not submitted yet') === 0) {
            return self::STATUS_LONG;
        }

        return $status !== '' ? $status : '—';
    }

    /**
     * Untitled / missing form-number placeholder, e.g. "RIS (in preparation)".
     */
    public static function untitled(string $docKey): string
    {
        $doc = match (strtolower(trim($docKey))) {
            'ris' => 'RIS',
            'atp', 'pr' => 'ATP',
            'rfc', 'request' => 'RFC',
            'dv' => 'DV',
            'rr' => 'RR',
            'lr', 'liq', 'liquidation' => 'LR',
            'po' => 'PO',
            default => strtoupper(trim($docKey)) ?: 'Record',
        };

        return $doc.' (in preparation)';
    }

    public static function untitledWithId(string $docKey, int|string|null $id): string
    {
        $base = self::untitled($docKey);
        $id = trim((string) $id);

        return $id !== '' ? $base.' #'.$id : $base;
    }

    public static function editTitle(string $docKey): string
    {
        $doc = match (strtolower(trim($docKey))) {
            'atp', 'pr' => 'ATP',
            'ris' => 'RIS',
            'rfc' => 'RFC',
            'dv' => 'DV',
            'rr' => 'RR',
            'lr', 'liq' => 'Liquidation',
            'po' => 'PO',
            default => strtoupper(trim($docKey)) ?: 'Record',
        };

        return 'Edit '.$doc.' (in preparation)';
    }

    public static function createAction(string $docKey = 'DV'): string
    {
        $doc = strtoupper(trim($docKey)) ?: 'DV';

        return 'Create '.$doc;
    }

    public static function viewList(): string
    {
        return 'View in preparation';
    }

    public static function copyAction(): string
    {
        return 'Copy';
    }

    public static function deleteAction(string $docKey = ''): string
    {
        $doc = trim($docKey);

        return $doc !== '' ? 'Delete '.$doc : 'Delete';
    }

    public static function deleteConfirm(string $docKey = 'record'): string
    {
        return 'Delete this '.trim($docKey).' that is still in preparation? This cannot be undone.';
    }

    /** Filter option label while value stays Draft. */
    public static function filterOption(): string
    {
        return self::STATUS;
    }

    public static function handoverInboxTitle(): string
    {
        return 'In-preparation records passed to you';
    }

    public static function handoverCountTitle(string $docKey, int $count): string
    {
        $doc = strtoupper(trim($docKey)) ?: 'Record';
        $noun = $count === 1 ? $doc.' record' : $doc.' records';

        return $count.' '.$noun.' in preparation passed to you';
    }

    public static function flashSaved(string $docName): string
    {
        return trim($docName).' saved (in preparation).';
    }

    public static function flashCreated(string $docName): string
    {
        return trim($docName).' created (in preparation).';
    }

    public static function flashUpdated(string $docName): string
    {
        return trim($docName).' updated (in preparation).';
    }

    public static function flashDeleted(string $docName): string
    {
        return trim($docName).' deleted.';
    }

    public static function onlyEditableError(string $docName): string
    {
        return 'Only in-preparation or revision '.trim($docName).' can be edited.';
    }

    public static function importanceCount(int $count, string $docPlural): string
    {
        return $count.' '.$docPlural.' in preparation to finish';
    }
}
