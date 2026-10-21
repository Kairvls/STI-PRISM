<?php

namespace App\Support;

/**
 * Canonical equipment report (ticket) status values.
 *
 * Dropdown order follows chronological workflow:
 * Pending → Processing → For Replacement → Resolved → Rejected → Closed / Cancelled
 */
class ReportStatuses
{
    public const PENDING = 'Pending';

    public const PROCESSING = 'Processing';

    public const FOR_REPLACEMENT = 'For Replacement';

    public const RESOLVED = 'Resolved';

    public const REJECTED = 'Rejected';

    public const CLOSED_CANCELLED = 'Closed / Cancelled';

    /** @var list<string> */
    public const ALL = [
        self::PENDING,
        self::PROCESSING,
        self::FOR_REPLACEMENT,
        self::RESOLVED,
        self::REJECTED,
        self::CLOSED_CANCELLED,
    ];

    /** Statuses still being worked (not terminal). */
    public static function open(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::FOR_REPLACEMENT,
        ];
    }

    /** Terminal / housekeeping statuses (clear overdue flags). */
    public static function closed(): array
    {
        return [
            self::RESOLVED,
            self::REJECTED,
            self::CLOSED_CANCELLED,
            self::FOR_REPLACEMENT,
        ];
    }

    /**
     * Allowed maintenance status transitions.
     *
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            self::PENDING => [
                self::PROCESSING,
                self::REJECTED,
                self::CLOSED_CANCELLED,
            ],
            self::PROCESSING => [
                self::FOR_REPLACEMENT,
                self::RESOLVED,
                self::REJECTED,
                self::CLOSED_CANCELLED,
            ],
        ];
    }

    /** Statuses maintenance can set via update form (excludes Pending as a target). */
    public static function updatableTargets(): array
    {
        return [
            self::PROCESSING,
            self::FOR_REPLACEMENT,
            self::RESOLVED,
            self::REJECTED,
            self::CLOSED_CANCELLED,
        ];
    }

    public static function ruleIn(): string
    {
        return 'in:'.implode(',', self::ALL);
    }

    public static function updateRuleIn(): string
    {
        return 'in:'.implode(',', self::updatableTargets());
    }
}
