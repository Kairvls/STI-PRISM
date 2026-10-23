<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records which purchaser-sidebar items this user has already opened.
 * A record stays off the badge until its status changes.
 */
class PurchaserMenuSeen
{
    public const TABLE = 'purchaser_sidebar_seen_table';

    public static function capture(Request $request): void
    {
        if (! $request->isMethod('GET') || ! Auth::check() || ! Schema::hasTable(self::TABLE)) {
            return;
        }

        if ($request->query('view') === 'archive' || $request->filled('search')) {
            return;
        }

        $name = $request->route()?->getName();

        match ($name) {
            'purchaser.procurement.replacement-requests' => self::captureList('replacement', $request),
            'purchaser.atp.index' => self::captureList('atp', $request),
            'purchaser.atp.show', 'purchaser.atp.edit' => self::markRecord('atp', (int) $request->route('id')),
            'purchaser.rfc.index' => self::captureList('rfc', $request, self::fundBucket($request->query('fund'))),
            'purchaser.rr.index' => self::captureList('rr', $request),
            'purchaser.bo.index' => self::captureList('bo', $request, self::backOrderBucket($request->query('payment'))),
            'purchaser.liq.index' => self::captureList('liq', $request),
            'purchaser.dashboard.preview' => self::capturePreview($request),
            default => null,
        };
    }

    /**
     * Hide rows this user already opened while the status is still the one they saw.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public static function excludeSeen($query, string $kind, string $idColumn, string $statusColumn): void
    {
        $userId = (int) Auth::id();
        if ($userId < 1 || ! Schema::hasTable(self::TABLE)) {
            return;
        }

        $query->whereNotExists(function ($sub) use ($userId, $kind, $idColumn, $statusColumn) {
            $sub->select(DB::raw(1))
                ->from(self::TABLE.' as menu_seen')
                ->where('menu_seen.seen_user_id', $userId)
                ->where('menu_seen.seen_kind', $kind)
                ->whereColumn('menu_seen.seen_record_id', $idColumn)
                ->whereRaw("BINARY menu_seen.seen_status = BINARY COALESCE({$statusColumn}, '')");
        });
    }

    private static function captureList(string $kind, Request $request, ?string $bucket = null): void
    {
        if ($request->filled('record')) {
            self::markRecord($kind, (int) $request->query('record'));

            return;
        }

        $rows = PurchaserSidebarCounts::openRecords($kind, $bucket);
        $status = $request->filled('status') ? (string) $request->query('status') : null;
        if ($status !== null && $status !== 'unresolved') {
            $rows = array_filter($rows, fn ($current) => $current === $status);
        }

        self::markPairs($kind, $rows);
    }

    private static function capturePreview(Request $request): void
    {
        $type = strtolower((string) $request->route('type'));
        if (! in_array($type, ['atp', 'rfc', 'rr', 'liq'], true)) {
            return;
        }

        self::markRecord($type, (int) $request->route('id'));
    }

    private static function markRecord(string $kind, int $id): void
    {
        if ($id < 1) {
            return;
        }

        $rows = PurchaserSidebarCounts::openRecords($kind);
        if (! array_key_exists($id, $rows)) {
            return;
        }

        self::markPairs($kind, [$id => $rows[$id]]);
    }

    /**
     * @param  array<int, string>  $rows  record id => status
     */
    public static function remember(string $kind, array $rows): void
    {
        self::markPairs($kind, $rows);
    }

    /**
     * @param  array<int, string>  $rows  record id => status
     */
    private static function markPairs(string $kind, array $rows): void
    {
        $userId = (int) Auth::id();
        if ($userId < 1 || $rows === [] || ! Schema::hasTable(self::TABLE)) {
            return;
        }

        $now = now();
        $payload = [];
        foreach ($rows as $id => $status) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $payload[] = [
                'seen_user_id' => $userId,
                'seen_kind' => $kind,
                'seen_record_id' => $id,
                'seen_status' => mb_substr((string) $status, 0, 40),
                'seen_at' => $now,
            ];
        }

        foreach (array_chunk($payload, 200) as $chunk) {
            DB::table(self::TABLE)->upsert(
                $chunk,
                ['seen_user_id', 'seen_kind', 'seen_record_id'],
                ['seen_status', 'seen_at']
            );
        }
    }

    private static function fundBucket(mixed $fund): ?string
    {
        return match (ProcurementPaymentPath::normalize(is_string($fund) ? $fund : null)) {
            ProcurementPaymentPath::DIRECT_PAYMENT => 'dp',
            ProcurementPaymentPath::CASH_INSTALLMENT => 'ci',
            ProcurementPaymentPath::CASH_ADVANCE => 'ca',
            default => null,
        };
    }

    private static function backOrderBucket(mixed $payment): ?string
    {
        return match ($payment) {
            'rfc', 'dp', 'ci' => 'rfc',
            'ca' => 'ca',
            default => null,
        };
    }
}
