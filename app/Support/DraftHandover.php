<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Pass a draft RIS / ATP / PO to another purchaser. Ownership only moves once
 * the recipient accepts; until then the sender still owns (and may edit) the draft.
 */
class DraftHandover
{
    public const TABLE = 'document_handovers_table';

    public const STATUS_PENDING = 'Pending';
    public const STATUS_ACCEPTED = 'Accepted';
    public const STATUS_DECLINED = 'Declined';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_EXPIRED = 'Expired';

    public const TYPES = ['ris', 'atp', 'po'];

    private const TYPE_LABELS = ['ris' => 'RIS', 'atp' => 'ATP', 'po' => 'Purchase Order'];

    private const INDEX_ROUTES = [
        'ris' => 'purchaser.ris.index',
        'atp' => 'purchaser.atp.index',
        'po' => 'purchaser.purchase-orders.index',
    ];

    public static function enabled(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? strtoupper($type);
    }

    /**
     * Other users holding the Purchaser role (primary or additional).
     *
     * @return array<int, array{id: int, name: string}>
     */
    public static function coworkers(?int $excludeUserId = null): array
    {
        $excludeUserId ??= (int) Auth::id();

        return DB::table('users_table')
            ->where('user_id', '!=', $excludeUserId)
            ->orderBy('user_full_name')
            ->get(['user_id', 'user_role_id', 'user_full_name'])
            ->filter(fn ($user) => RoleAccess::hasRole(PurchaserDocumentAccess::PURCHASER_ROLE_ID, $user))
            ->map(fn ($user) => ['id' => (int) $user->user_id, 'name' => (string) $user->user_full_name])
            ->values()
            ->all();
    }

    public static function userName(?int $userId): string
    {
        if (! $userId) {
            return 'A co-worker';
        }

        return (string) (DB::table('users_table')->where('user_id', $userId)->value('user_full_name') ?: 'A co-worker');
    }

    public static function document(string $type, int $id): ?object
    {
        return match ($type) {
            'ris' => DB::table('requisition_issue_slip_table')->where('ris_id', $id)->first(),
            'atp' => DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->first(),
            'po' => PurchaseOrderBasket::tablesExist()
                ? DB::table('purchase_orders_table')->where('purchase_order_id', $id)->first()
                : null,
            default => null,
        };
    }

    public static function label(string $type, object $doc): string
    {
        return match ($type) {
            'ris' => filled($doc->ris_form_number ?? null) ? (string) $doc->ris_form_number : 'Draft RIS #'.$doc->ris_id,
            'atp' => filled($doc->authority_purchase_form_number ?? null)
                ? (string) $doc->authority_purchase_form_number
                : 'Draft ATP #'.$doc->authority_purchase_id,
            'po' => PurchaseOrderBasket::displayNumber($doc),
            default => '',
        };
    }

    /**
     * Why this draft cannot be passed on right now, or null when it can.
     */
    public static function blockReason(string $type, ?object $doc, int $ownerId): ?string
    {
        if (! $doc) {
            return 'This draft no longer exists.';
        }

        if (PurchaserDocumentAccess::ownerId($doc, $type) !== $ownerId) {
            return 'Only the owner of this draft can pass it to a co-worker.';
        }

        switch ($type) {
            case 'ris':
                if ((string) ($doc->ris_status ?? '') !== 'Draft') {
                    return 'Only Draft RIS records can be passed to a co-worker.';
                }
                if ((int) ($doc->ris_is_archived ?? 0) === 1) {
                    return 'Restore this RIS before passing it on.';
                }
                break;

            case 'atp':
                if ((string) ($doc->authority_purchase_status ?? '') !== 'Pending' || $doc->authority_purchase_submitted_at !== null) {
                    return 'Only draft ATPs can be passed to a co-worker.';
                }
                if ((int) ($doc->authority_purchase_is_archived ?? 0) === 1) {
                    return 'Restore this ATP before passing it on.';
                }
                if ($poId = PurchaseOrderBasket::poIdForAtp((int) $doc->authority_purchase_id)) {
                    $order = self::document('po', $poId);

                    return 'This ATP is part of '.($order ? PurchaseOrderBasket::displayNumber($order) : 'a Purchase Order')
                        .'. Pass the whole Purchase Order instead.';
                }
                break;

            case 'po':
                if ((string) ($doc->purchase_order_status ?? '') !== PurchaseOrderBasket::STATUS_DRAFT) {
                    return 'Only draft Purchase Orders can be passed to a co-worker.';
                }
                if ((int) ($doc->purchase_order_is_archived ?? 0) === 1) {
                    return 'Restore this Purchase Order before passing it on.';
                }
                break;

            default:
                return 'Unsupported document.';
        }

        return null;
    }

    public static function pendingFor(string $type, int $docId): ?object
    {
        if (! self::enabled()) {
            return null;
        }

        return DB::table(self::TABLE)
            ->where('handover_document_type', $type)
            ->where('handover_document_id', $docId)
            ->where('handover_status', self::STATUS_PENDING)
            ->orderByDesc('handover_id')
            ->first();
    }

    public static function request(string $type, int $docId, int $toUserId, ?string $note): int
    {
        $fromUserId = (int) Auth::id();

        if (! self::enabled() || ! in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Passing drafts is not available.');
        }

        if ($toUserId === $fromUserId) {
            throw new RuntimeException('Choose a co-worker other than yourself.');
        }

        if (! collect(self::coworkers($fromUserId))->contains('id', $toUserId)) {
            throw new RuntimeException('Choose a co-worker who has the Purchaser role.');
        }

        return DB::transaction(function () use ($type, $docId, $toUserId, $fromUserId, $note) {
            $doc = self::lockDocument($type, $docId);

            if ($reason = self::blockReason($type, $doc, $fromUserId)) {
                throw new RuntimeException($reason);
            }

            if ($pending = self::pendingFor($type, $docId)) {
                throw new RuntimeException('This draft is already waiting for '.self::userName((int) $pending->handover_to_user_id).' to accept it.');
            }

            $handoverId = (int) DB::table(self::TABLE)->insertGetId([
                'handover_document_type' => $type,
                'handover_document_id' => $docId,
                'handover_from_user_id' => $fromUserId,
                'handover_to_user_id' => $toUserId,
                'handover_status' => self::STATUS_PENDING,
                'handover_note' => filled($note) ? trim((string) $note) : null,
                'handover_created_at' => now(),
            ]);

            $label = self::label($type, $doc);
            WorkflowNotifier::toUser(
                $toUserId,
                WorkflowNotifier::ROLE_PURCHASER,
                self::typeLabel($type).' passed to you',
                self::userName($fromUserId).' wants to pass you '.$label.'. Accept it to take over the draft.',
                'draft_handover',
                strtoupper($type),
                $docId,
                route(self::INDEX_ROUTES[$type])
            );

            return $handoverId;
        });
    }

    public static function accept(int $handoverId): object
    {
        $userId = (int) Auth::id();

        $result = DB::transaction(function () use ($handoverId, $userId) {
            $handover = self::lockPending($handoverId);

            if ((int) $handover->handover_to_user_id !== $userId) {
                throw new RuntimeException('This draft was not passed to you.');
            }

            $type = (string) $handover->handover_document_type;
            $docId = (int) $handover->handover_document_id;
            $fromUserId = (int) $handover->handover_from_user_id;
            $doc = self::lockDocument($type, $docId);

            if (self::blockReason($type, $doc, $fromUserId) !== null) {
                self::close($handoverId, self::STATUS_EXPIRED);

                return null;
            }

            self::transferOwnership($type, $docId, $userId);
            self::close($handoverId, self::STATUS_ACCEPTED);

            $label = self::label($type, $doc);
            WorkflowNotifier::toUser(
                $fromUserId,
                WorkflowNotifier::ROLE_PURCHASER,
                self::typeLabel($type).' handover accepted',
                self::userName($userId).' accepted '.$label.'. It is now in their drafts.',
                'draft_handover_accepted',
                strtoupper($type),
                $docId,
                route(self::INDEX_ROUTES[$type])
            );

            return (object) ['type' => $type, 'id' => $docId, 'label' => $label];
        });

        if ($result === null) {
            throw new RuntimeException('This draft changed after it was passed to you (it may have been submitted or deleted), so it can no longer be accepted.');
        }

        return $result;
    }

    public static function decline(int $handoverId, ?string $reason): object
    {
        $userId = (int) Auth::id();

        return DB::transaction(function () use ($handoverId, $userId, $reason) {
            $handover = self::lockPending($handoverId);

            if ((int) $handover->handover_to_user_id !== $userId) {
                throw new RuntimeException('This draft was not passed to you.');
            }

            self::close($handoverId, self::STATUS_DECLINED, $reason);

            $type = (string) $handover->handover_document_type;
            $docId = (int) $handover->handover_document_id;
            $doc = self::document($type, $docId);
            $label = $doc ? self::label($type, $doc) : self::typeLabel($type).' #'.$docId;

            WorkflowNotifier::toUser(
                (int) $handover->handover_from_user_id,
                WorkflowNotifier::ROLE_PURCHASER,
                self::typeLabel($type).' handover declined',
                self::userName($userId).' declined '.$label.'.'.(filled($reason) ? ' Reason: '.trim((string) $reason) : '').' The draft stays with you.',
                'draft_handover_declined',
                strtoupper($type),
                $docId,
                route(self::INDEX_ROUTES[$type])
            );

            return (object) ['type' => $type, 'id' => $docId, 'label' => $label];
        });
    }

    public static function cancel(int $handoverId): object
    {
        $userId = (int) Auth::id();

        return DB::transaction(function () use ($handoverId, $userId) {
            $handover = self::lockPending($handoverId);

            if ((int) $handover->handover_from_user_id !== $userId) {
                throw new RuntimeException('Only the sender can cancel this handover.');
            }

            self::close($handoverId, self::STATUS_CANCELLED);

            $type = (string) $handover->handover_document_type;
            $docId = (int) $handover->handover_document_id;
            $doc = self::document($type, $docId);
            $label = $doc ? self::label($type, $doc) : self::typeLabel($type).' #'.$docId;

            WorkflowNotifier::toUser(
                (int) $handover->handover_to_user_id,
                WorkflowNotifier::ROLE_PURCHASER,
                self::typeLabel($type).' handover cancelled',
                self::userName($userId).' took back '.$label.'.',
                'draft_handover_cancelled',
                strtoupper($type),
                $docId,
                route(self::INDEX_ROUTES[$type])
            );

            return (object) ['type' => $type, 'id' => $docId, 'label' => $label];
        });
    }

    /**
     * Pending handovers the current user sent, keyed by document id.
     *
     * @return Collection<int, object>
     */
    public static function outgoing(string $type): Collection
    {
        if (! self::enabled()) {
            return collect();
        }

        return self::pendingQuery()
            ->where('handover_document_type', $type)
            ->where('handover_from_user_id', (int) Auth::id())
            ->get()
            ->filter(fn ($handover) => self::stillValid($handover))
            ->keyBy(fn ($handover) => (int) $handover->handover_document_id);
    }

    /**
     * Pending handovers waiting on the current user, with a short summary of each draft.
     *
     * @return Collection<int, object>
     */
    public static function incoming(string $type): Collection
    {
        if (! self::enabled()) {
            return collect();
        }

        return self::pendingQuery()
            ->where('handover_document_type', $type)
            ->where('handover_to_user_id', (int) Auth::id())
            ->orderBy('handover_id')
            ->get()
            ->filter(fn ($handover) => self::stillValid($handover))
            ->map(function ($handover) use ($type) {
                $doc = self::document($type, (int) $handover->handover_document_id);
                $handover->document_label = self::label($type, $doc);
                $handover->document_summary = self::summary($type, $doc);

                return $handover;
            })
            ->values();
    }

    /**
     * Declined handovers the current user sent and hasn't dismissed yet, newest per draft,
     * keyed by document id. Superseded by any later handover of the same draft.
     *
     * @return Collection<int, object>
     */
    public static function declinedForSender(string $type): Collection
    {
        if (! self::enabled() || ! Schema::hasColumn(self::TABLE, 'handover_sender_dismissed_at')) {
            return collect();
        }

        return DB::table(self::TABLE)
            ->leftJoin('users_table as to_user', 'to_user.user_id', '=', self::TABLE.'.handover_to_user_id')
            ->where(self::TABLE.'.handover_document_type', $type)
            ->where(self::TABLE.'.handover_from_user_id', (int) Auth::id())
            ->where(self::TABLE.'.handover_status', self::STATUS_DECLINED)
            ->whereNull(self::TABLE.'.handover_sender_dismissed_at')
            ->whereNotExists(function ($q) {
                $q->from(self::TABLE.' as newer')
                    ->whereColumn('newer.handover_document_type', self::TABLE.'.handover_document_type')
                    ->whereColumn('newer.handover_document_id', self::TABLE.'.handover_document_id')
                    ->whereColumn('newer.handover_id', '>', self::TABLE.'.handover_id');
            })
            ->orderByDesc(self::TABLE.'.handover_id')
            ->select(self::TABLE.'.*', 'to_user.user_full_name as to_name')
            ->get()
            ->map(function ($handover) use ($type) {
                $doc = self::document($type, (int) $handover->handover_document_id);
                $handover->document_label = $doc ? self::label($type, $doc) : self::typeLabel($type).' #'.$handover->handover_document_id;

                return $handover;
            })
            ->keyBy(fn ($handover) => (int) $handover->handover_document_id);
    }

    public static function dismiss(int $handoverId): void
    {
        $updated = DB::table(self::TABLE)
            ->where('handover_id', $handoverId)
            ->where('handover_from_user_id', (int) Auth::id())
            ->whereNotIn('handover_status', [self::STATUS_PENDING])
            ->update(['handover_sender_dismissed_at' => now()]);

        if (! $updated) {
            throw new RuntimeException('Handover not found.');
        }
    }

    /**
     * The handover a preview is being opened for, when the current user is its sender or recipient.
     */
    public static function forPreview(int $handoverId): object
    {
        $handover = self::enabled() ? DB::table(self::TABLE)->where('handover_id', $handoverId)->first() : null;
        $userId = (int) Auth::id();

        if (! $handover || ! in_array($userId, [(int) $handover->handover_to_user_id, (int) $handover->handover_from_user_id], true)) {
            throw new RuntimeException('Handover not found.');
        }

        if ($handover->handover_status !== self::STATUS_PENDING) {
            throw new RuntimeException('This handover was already '.strtolower((string) $handover->handover_status).'.');
        }

        $handover->from_name = self::userName((int) $handover->handover_from_user_id);

        return $handover;
    }

    /**
     * Pending handovers waiting on the current user, counted per document type.
     *
     * @return array{ris: int, atp: int, po: int, total: int}
     */
    public static function incomingCounts(): array
    {
        static $cache = [];

        $userId = (int) Auth::id();
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        $counts = ['ris' => 0, 'atp' => 0, 'po' => 0, 'total' => 0];

        if ($userId > 0 && self::enabled()) {
            $pending = DB::table(self::TABLE)
                ->where('handover_status', self::STATUS_PENDING)
                ->where('handover_to_user_id', $userId)
                ->get();

            foreach ($pending as $handover) {
                $type = (string) $handover->handover_document_type;
                if (isset($counts[$type]) && self::stillValid($handover)) {
                    $counts[$type]++;
                    $counts['total']++;
                }
            }
        }

        return $cache[$userId] = $counts;
    }

    /**
     * Purchaser page holding the first waiting handover, for "passed to you" links.
     */
    public static function inboxUrl(): string
    {
        $counts = self::incomingCounts();

        foreach (self::TYPES as $type) {
            if ($counts[$type] > 0) {
                return route(self::INDEX_ROUTES[$type]);
            }
        }

        return route(self::INDEX_ROUTES['ris']);
    }

    private static function pendingQuery()
    {
        return DB::table(self::TABLE)
            ->leftJoin('users_table as from_user', 'from_user.user_id', '=', self::TABLE.'.handover_from_user_id')
            ->leftJoin('users_table as to_user', 'to_user.user_id', '=', self::TABLE.'.handover_to_user_id')
            ->where(self::TABLE.'.handover_status', self::STATUS_PENDING)
            ->select(
                self::TABLE.'.*',
                'from_user.user_full_name as from_name',
                'to_user.user_full_name as to_name'
            );
    }

    /**
     * A pending handover whose draft was submitted, deleted, or re-owned is closed as Expired.
     */
    private static function stillValid(object $handover): bool
    {
        $type = (string) $handover->handover_document_type;
        $doc = self::document($type, (int) $handover->handover_document_id);

        if (self::blockReason($type, $doc, (int) $handover->handover_from_user_id) === null) {
            return true;
        }

        self::close((int) $handover->handover_id, self::STATUS_EXPIRED);

        return false;
    }

    private static function summary(string $type, object $doc): string
    {
        switch ($type) {
            case 'ris':
                $items = DB::table('requisition_issue_slip_items_table')->where('ris_id', $doc->ris_id);
                $parts = [
                    RisWorkflow::urgencyLabel($doc),
                    $items->count().' item(s)',
                    '₱'.number_format((float) (clone $items)->sum('ris_total_amount'), 2),
                ];
                $title = trim((string) ($doc->ris_manual_title ?? '')) ?: trim((string) ($doc->ris_purpose_description ?? ''));
                if ($title !== '') {
                    array_unshift($parts, \Illuminate\Support\Str::limit($title, 60));
                }

                return implode(' · ', $parts);

            case 'atp':
                $items = DB::table('authority_to_purchase_items_table')->where('authority_purchase_id', $doc->authority_purchase_id);
                $risNo = $doc->authority_purchase_ris_id
                    ? DB::table('requisition_issue_slip_table')->where('ris_id', $doc->authority_purchase_ris_id)->value('ris_form_number')
                    : null;

                return implode(' · ', array_filter([
                    $risNo ? 'RIS '.$risNo : null,
                    $items->count().' item(s)',
                    '₱'.number_format((float) (clone $items)->sum('atp_amount'), 2),
                ]));

            case 'po':
                $atpIds = PurchaseOrderBasket::atpIdsForPo((int) $doc->purchase_order_id);
                $total = $atpIds === []
                    ? 0
                    : (float) DB::table('authority_to_purchase_items_table')->whereIn('authority_purchase_id', $atpIds)->sum('atp_amount');

                return count($atpIds).' ATP(s) · ₱'.number_format($total, 2);
        }

        return '';
    }

    private static function transferOwnership(string $type, int $docId, int $toUserId): void
    {
        $toName = self::userName($toUserId);

        if ($type === 'ris') {
            $update = [
                'ris_created_by' => $toUserId,
                'ris_requested_by_signature' => $toName,
                'ris_updated_at' => now(),
            ];
            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_requested_by_signature_image')) {
                $update['ris_requested_by_signature_image'] = null;
            }
            DB::table('requisition_issue_slip_table')->where('ris_id', $docId)->update($update);

            return;
        }

        if ($type === 'atp') {
            self::transferAtp($docId, $toUserId, $toName);
            PurchaseOrderBasket::groupDraftAtp($docId, $toUserId);

            return;
        }

        if ($type === 'po') {
            DB::table('purchase_orders_table')->where('purchase_order_id', $docId)->update([
                'purchase_order_created_by' => $toUserId,
                'purchase_order_updated_at' => now(),
            ]);

            foreach (PurchaseOrderBasket::atpIdsForPo($docId) as $atpId) {
                self::transferAtp($atpId, $toUserId, $toName);
            }
        }
    }

    private static function transferAtp(int $atpId, int $toUserId, string $toName): void
    {
        $update = [
            'authority_purchase_created_by' => $toUserId,
            'authority_purchase_received_by_name' => $toName,
            'authority_purchase_updated_at' => now(),
        ];
        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_received_by_signature')) {
            $update['authority_purchase_received_by_signature'] = null;
        }

        DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->update($update);
    }

    private static function lockDocument(string $type, int $id): ?object
    {
        return match ($type) {
            'ris' => DB::table('requisition_issue_slip_table')->where('ris_id', $id)->lockForUpdate()->first(),
            'atp' => DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->lockForUpdate()->first(),
            'po' => DB::table('purchase_orders_table')->where('purchase_order_id', $id)->lockForUpdate()->first(),
            default => null,
        };
    }

    private static function lockPending(int $handoverId): object
    {
        $handover = self::enabled()
            ? DB::table(self::TABLE)->where('handover_id', $handoverId)->lockForUpdate()->first()
            : null;

        if (! $handover) {
            throw new RuntimeException('Handover not found.');
        }

        if ($handover->handover_status !== self::STATUS_PENDING) {
            throw new RuntimeException('This handover was already '.strtolower((string) $handover->handover_status).'.');
        }

        return $handover;
    }

    private static function close(int $handoverId, string $status, ?string $responseNote = null): void
    {
        DB::table(self::TABLE)->where('handover_id', $handoverId)->update([
            'handover_status' => $status,
            'handover_response_note' => filled($responseNote) ? trim((string) $responseNote) : null,
            'handover_responded_at' => now(),
        ]);
    }
}
