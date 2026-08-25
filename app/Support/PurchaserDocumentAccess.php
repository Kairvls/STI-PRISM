<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Ownership helpers for purchaser-authored procurement documents.
 * Enforced only for purchaser role (user_role_id = 3); other roles bypass.
 */
class PurchaserDocumentAccess
{
    public const PURCHASER_ROLE_ID = 3;

    public static function isPurchaser(?object $user = null): bool
    {
        $user = $user ?? Auth::user();

        return $user && (int) ($user->user_role_id ?? 0) === self::PURCHASER_ROLE_ID;
    }

    /**
     * Abort 403 unless the current purchaser owns the record.
     * Legacy rows with no owner remain accessible to keep existing data usable.
     */
    public static function assertOwns(object $record, string $document): void
    {
        if (!self::isPurchaser()) {
            return;
        }

        if (!self::owns($record, $document)) {
            abort(403, 'You do not have access to this document.');
        }
    }

    public static function owns(object $record, string $document): bool
    {
        $userId = (int) (Auth::id() ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $ownerId = self::ownerId($record, $document);

        // Legacy / unowned records: visible to any purchaser.
        if ($ownerId === null) {
            return true;
        }

        return $ownerId === $userId;
    }

    public static function ownerId(object $record, string $document): ?int
    {
        $fields = match ($document) {
            'ris' => ['ris_created_by', 'ris_submitted_by'],
            'atp' => ['authority_purchase_created_by', 'authority_purchase_submitted_by'],
            'rfc' => ['request_check_requested_by_user_id', 'request_check_submitted_by'],
            'rr' => ['receiving_report_created_by', 'receiving_report_submitted_by'],
            'liq' => ['liquidation_report_created_by', 'liquidation_report_submitted_by'],
            default => [],
        };

        foreach ($fields as $field) {
            if (isset($record->{$field}) && $record->{$field} !== null && $record->{$field} !== '') {
                return (int) $record->{$field};
            }
        }

        return null;
    }

    /**
     * Scope a query builder to the current purchaser's documents when applicable.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    public static function scopeOwned($query, string $document, ?string $table = null)
    {
        if (!self::isPurchaser()) {
            return $query;
        }

        $userId = (int) Auth::id();
        $prefix = $table ? rtrim($table, '.').'.' : '';

        return match ($document) {
            'ris' => self::scopeRis($query, $prefix, $userId),
            'atp' => self::scopeAtp($query, $prefix, $userId),
            'rfc' => self::scopeRfc($query, $prefix, $userId),
            'rr' => self::scopeRr($query, $prefix, $userId),
            'liq' => self::scopeLiq($query, $prefix, $userId),
            default => $query,
        };
    }

    private static function scopeRis($query, string $prefix, int $userId)
    {
        $hasCreated = Schema::hasColumn('requisition_issue_slip_table', 'ris_created_by');

        return $query->where(function ($q) use ($prefix, $userId, $hasCreated) {
            if ($hasCreated) {
                $q->where($prefix.'ris_created_by', $userId)
                    ->orWhere(function ($legacy) use ($prefix, $userId) {
                        $legacy->whereNull($prefix.'ris_created_by')
                            ->where(function ($sub) use ($prefix, $userId) {
                                $sub->where($prefix.'ris_submitted_by', $userId)
                                    ->orWhereNull($prefix.'ris_submitted_by');
                            });
                    });
            } else {
                $q->where($prefix.'ris_submitted_by', $userId)
                    ->orWhereNull($prefix.'ris_submitted_by');
            }
        });
    }

    private static function scopeAtp($query, string $prefix, int $userId)
    {
        return $query->where(function ($q) use ($prefix, $userId) {
            $q->where($prefix.'authority_purchase_created_by', $userId)
                ->orWhere(function ($legacy) use ($prefix, $userId) {
                    $legacy->whereNull($prefix.'authority_purchase_created_by')
                        ->where(function ($sub) use ($prefix, $userId) {
                            $sub->where($prefix.'authority_purchase_submitted_by', $userId)
                                ->orWhereNull($prefix.'authority_purchase_submitted_by');
                        });
                });
        });
    }

    private static function scopeRfc($query, string $prefix, int $userId)
    {
        return $query->where(function ($q) use ($prefix, $userId) {
            $q->where($prefix.'request_check_requested_by_user_id', $userId)
                ->orWhere(function ($legacy) use ($prefix, $userId) {
                    $legacy->whereNull($prefix.'request_check_requested_by_user_id')
                        ->where(function ($sub) use ($prefix, $userId) {
                            $sub->where($prefix.'request_check_submitted_by', $userId)
                                ->orWhereNull($prefix.'request_check_submitted_by');
                        });
                });
        });
    }

    private static function scopeRr($query, string $prefix, int $userId)
    {
        $hasCreated = Schema::hasColumn('receiving_reports_table', 'receiving_report_created_by');

        return $query->where(function ($q) use ($prefix, $userId, $hasCreated) {
            if ($hasCreated) {
                $q->where($prefix.'receiving_report_created_by', $userId)
                    ->orWhere(function ($legacy) use ($prefix, $userId) {
                        $legacy->whereNull($prefix.'receiving_report_created_by')
                            ->where(function ($sub) use ($prefix, $userId) {
                                $sub->where($prefix.'receiving_report_submitted_by', $userId)
                                    ->orWhereNull($prefix.'receiving_report_submitted_by');
                            });
                    });
            } else {
                $q->where($prefix.'receiving_report_submitted_by', $userId)
                    ->orWhereNull($prefix.'receiving_report_submitted_by');
            }
        });
    }

    private static function scopeLiq($query, string $prefix, int $userId)
    {
        $hasCreated = Schema::hasColumn('liquidation_reports_table', 'liquidation_report_created_by');

        return $query->where(function ($q) use ($prefix, $userId, $hasCreated) {
            if ($hasCreated) {
                $q->where($prefix.'liquidation_report_created_by', $userId)
                    ->orWhere(function ($legacy) use ($prefix, $userId) {
                        $legacy->whereNull($prefix.'liquidation_report_created_by')
                            ->where(function ($sub) use ($prefix, $userId) {
                                $sub->where($prefix.'liquidation_report_submitted_by', $userId)
                                    ->orWhereNull($prefix.'liquidation_report_submitted_by');
                            });
                    });
            } else {
                $q->where($prefix.'liquidation_report_submitted_by', $userId)
                    ->orWhereNull($prefix.'liquidation_report_submitted_by');
            }
        });
    }
}
