<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Back orders: RR rows the Purchaser marked missing or damaged at the first check.
 * They are resolved by replacement rows added to the same RR (verified again at second count);
 * the RR stays Incomplete until every back order is delivered.
 */
class BackOrders
{
    public const TABLE = 'back_orders_table';

    public const STATUS_OPEN = 'open';
    public const STATUS_WAITING_RESTOCK = 'waiting_restock';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_RECEIVING = 'receiving';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_FULFILLED_REPLACEMENT = 'fulfilled_replacement';

    public const UNRESOLVED = [
        self::STATUS_OPEN,
        self::STATUS_WAITING_RESTOCK,
        self::STATUS_REFUNDED,
        self::STATUS_RECEIVING,
    ];

    /** Statuses where the Purchaser can still record a replacement purchase. */
    public const REPLACEABLE = [
        self::STATUS_OPEN,
        self::STATUS_WAITING_RESTOCK,
        self::STATUS_REFUNDED,
    ];

    public const STATUSES = [
        self::STATUS_OPEN => 'Needs action',
        self::STATUS_WAITING_RESTOCK => 'Waiting for restock',
        self::STATUS_REFUNDED => 'Refunded – buy replacement',
        self::STATUS_RECEIVING => 'Replacement for second count',
        self::STATUS_FULFILLED => 'Delivered',
        self::STATUS_FULFILLED_REPLACEMENT => 'Delivered – new supplier',
    ];

    public const REASONS = [
        'out_of_stock' => 'Out of stock',
        'wrong_delivery' => 'Wrong delivery',
        'damaged' => 'Damaged on arrival',
        'other' => 'Other',
    ];

    public const TYPES = [
        'short' => 'Missing',
        'damaged' => 'Damaged',
    ];

    /** RR statuses whose second-counted rows are physically received. */
    public const RECEIVED_RR_STATUSES = ['Completed', 'Accepted', 'Incomplete'];

    public const FILE_MAX = 5;
    public const FILE_MAX_KB = 10240;
    public const FILE_MIMES = 'jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx';

    public static function supported(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUSES[$status] ?? ucfirst(str_replace('_', ' ', (string) $status));
    }

    public static function reasonLabel(?string $reason): string
    {
        return $reason ? (self::REASONS[$reason] ?? ucfirst(str_replace('_', ' ', $reason))) : 'Not recorded';
    }

    public static function isUnresolved(?string $status): bool
    {
        return in_array($status, self::UNRESOLVED, true);
    }

    public static function find($id): ?object
    {
        if (! self::supported()) {
            return null;
        }

        return DB::table(self::TABLE)->where('back_order_id', (int) $id)->first();
    }

    /**
     * Keep back orders in step with the RR rows: missing = ordered − received, damaged = damaged qty.
     * Called when the Purchaser submits (first check) and again after second count ($verifying),
     * which also marks back orders delivered once their replacement row is verified.
     * Returns how many back orders were created.
     */
    public static function syncForRr(object $rr, bool $verifying = false): int
    {
        if (! self::supported()) {
            return 0;
        }

        $rrId = (int) $rr->receiving_report_id;
        $items = DB::table('receiving_report_items_table')
            ->where('receiving_report_id', $rrId)
            ->orderBy('receiving_report_item_id')
            ->get();
        if ($items->isEmpty()) {
            return 0;
        }

        $context = self::fundingContext($rr);
        $parents = DB::table(self::TABLE)
            ->whereIn('back_order_id', $items->pluck('receiving_report_item_back_order_id')->filter()->all())
            ->get()
            ->keyBy('back_order_id');
        $existing = DB::table(self::TABLE)
            ->whereIn('back_order_receiving_report_item_id', $items->pluck('receiving_report_item_id')->all())
            ->get()
            ->keyBy(fn ($bo) => $bo->back_order_receiving_report_item_id.':'.$bo->back_order_type);
        $now = now();
        $created = 0;

        foreach ($items as $item) {
            $itemId = (int) $item->receiving_report_item_id;
            $qty = max(0, (int) ($item->receiving_report_item_quantity ?? 0));
            $parent = $parents->get((int) ($item->receiving_report_item_back_order_id ?? 0));

            if (
                $parent && $verifying && ! empty($item->receiving_report_item_verified)
                && $parent->back_order_status === self::STATUS_RECEIVING
                && (int) $parent->back_order_replacement_item_id === $itemId
            ) {
                DB::table(self::TABLE)->where('back_order_id', $parent->back_order_id)->update([
                    'back_order_status' => filled($parent->back_order_replacement_supplier_name)
                        ? self::STATUS_FULFILLED_REPLACEMENT
                        : self::STATUS_FULFILLED,
                    'back_order_updated_at' => $now,
                    'back_order_resolved_at' => $now,
                ]);
            }

            $expected = $parent
                ? (int) $parent->back_order_quantity
                : (($item->receiving_report_item_ordered_qty ?? null) !== null ? (int) $item->receiving_report_item_ordered_qty : null);
            $counts = [
                'short' => $expected !== null ? max(0, $expected - $qty) : 0,
                'damaged' => self::damagedQty($item),
            ];

            foreach ($counts as $type => $count) {
                $bo = $existing->get($itemId.':'.$type);
                if ($count > 0 && ! $bo) {
                    BackOrderNumber::insert([
                        'back_order_receiving_report_id' => $rrId,
                        'back_order_receiving_report_item_id' => $itemId,
                        'back_order_root_receiving_report_id' => $rrId,
                        'back_order_atp_id' => $context['atp_id'],
                        'back_order_request_check_id' => $context['rfc_id'],
                        'back_order_payment_path' => $context['payment_path'],
                        'back_order_supplier_id' => $item->receiving_report_item_supplier_id ?? null,
                        'back_order_supplier_name' => self::supplierName($item, $rr),
                        'back_order_article' => mb_substr(trim((string) ($item->receiving_report_item_article ?? 'Item')), 0, 500),
                        'back_order_unit' => $item->receiving_report_item_unit ?? null,
                        'back_order_unit_price' => (float) ($item->receiving_report_item_unit_price ?? 0),
                        'back_order_quantity' => $count,
                        'back_order_type' => $type,
                        'back_order_reason' => $type === 'damaged' ? 'damaged' : null,
                        'back_order_remarks' => self::rowRemarks($item, $type),
                        'back_order_status' => self::STATUS_OPEN,
                        'back_order_created_at' => $now,
                        'back_order_updated_at' => $now,
                    ]);
                    $created++;
                } elseif ($count > 0 && (int) $bo->back_order_quantity !== $count && self::untouched($bo)) {
                    DB::table(self::TABLE)->where('back_order_id', $bo->back_order_id)->update([
                        'back_order_quantity' => $count,
                        'back_order_updated_at' => $now,
                    ]);
                } elseif ($count === 0 && $bo && self::untouched($bo)) {
                    self::deleteFiles(self::decodeFiles($bo->back_order_images));
                    DB::table(self::TABLE)->where('back_order_id', $bo->back_order_id)->delete();
                }
            }
        }

        return $created;
    }

    /**
     * Undo a recorded replacement purchase: the back order goes back to waiting for action
     * (or refunded, when a refund was already recorded). The replacement row itself is removed by the caller.
     */
    public static function clearReplacement(object $bo): void
    {
        self::deleteFiles(self::decodeFiles($bo->back_order_replacement_files ?? null));
        DB::table(self::TABLE)->where('back_order_id', $bo->back_order_id)->update([
            'back_order_status' => $bo->back_order_refund_amount !== null ? self::STATUS_REFUNDED : self::STATUS_OPEN,
            'back_order_replacement_item_id' => null,
            'back_order_replacement_supplier_id' => null,
            'back_order_replacement_supplier_name' => null,
            'back_order_replacement_quantity' => null,
            'back_order_replacement_unit_price' => null,
            'back_order_replacement_reference' => null,
            'back_order_replacement_files' => null,
            'back_order_cash_difference' => null,
            'back_order_cash_note' => null,
            'back_order_resolved_at' => null,
            'back_order_updated_at' => now(),
        ]);
    }

    /**
     * Label for each replacement row on an RR form, keyed by the back order it replaces.
     *
     * @param  iterable<int, mixed>  $rows
     * @return array<int, array{label: string, new: bool}>
     */
    public static function replacementLabels(iterable $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) (is_array($row) ? ($row['back_order_id'] ?? 0) : ($row->receiving_report_item_back_order_id ?? 0));
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if ($ids === [] || ! self::supported()) {
            return [];
        }

        $labels = [];
        foreach (DB::table(self::TABLE)->whereIn('back_order_id', array_unique($ids))->get() as $bo) {
            $isNew = filled($bo->back_order_replacement_supplier_name);
            $labels[(int) $bo->back_order_id] = [
                'new' => $isNew,
                'label' => BackOrderNumber::label($bo).' · Replacement for '.(int) $bo->back_order_quantity.' '.strtolower(self::TYPES[$bo->back_order_type] ?? 'missing')
                    .' · '.($isNew ? 'New supplier' : 'Same supplier'),
            ];
        }

        return $labels;
    }

    public static function hasVerifiedRows(int $rrId): bool
    {
        return Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_verified')
            && DB::table('receiving_report_items_table')
                ->where('receiving_report_id', $rrId)
                ->where('receiving_report_item_verified', 1)
                ->exists();
    }

    /**
     * Receiving rejected a replacement delivery: drop the unverified rows and reopen their back orders.
     * Returns how many rows were removed.
     */
    public static function rejectUnverifiedRows(int $rrId): int
    {
        $rowIds = DB::table('receiving_report_items_table')
            ->where('receiving_report_id', $rrId)
            ->where(fn ($q) => $q->whereNull('receiving_report_item_verified')->orWhere('receiving_report_item_verified', 0))
            ->pluck('receiving_report_item_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if ($rowIds === []) {
            return 0;
        }

        if (self::supported()) {
            foreach (DB::table(self::TABLE)->whereIn('back_order_replacement_item_id', $rowIds)->get() as $bo) {
                self::clearReplacement($bo);
            }
            self::deleteBackOrders(DB::table(self::TABLE)->whereIn('back_order_receiving_report_item_id', $rowIds)->get());
        }
        DB::table('receiving_report_items_table')->whereIn('receiving_report_item_id', $rowIds)->delete();

        return count($rowIds);
    }

    /**
     * The whole RR was returned at first delivery; its back orders no longer apply.
     */
    public static function discardForRr(int $rrId): void
    {
        if (self::supported()) {
            self::deleteBackOrders(self::forRoot($rrId));
        }
    }

    public static function deleteBackOrders(Collection $backOrders): void
    {
        foreach ($backOrders as $bo) {
            self::deleteFiles(array_merge(
                self::decodeFiles($bo->back_order_images),
                self::decodeFiles($bo->back_order_refund_images ?? null),
                self::decodeFiles($bo->back_order_replacement_files ?? null)
            ));
        }
        if ($backOrders->isNotEmpty()) {
            DB::table(self::TABLE)->whereIn('back_order_id', $backOrders->pluck('back_order_id')->all())->delete();
        }
    }

    /**
     * RR status after second count: Incomplete while any back order is still open.
     */
    public static function rrStatusAfterCount(int $rrId): string
    {
        return (self::unresolvedCounts([$rrId])[$rrId] ?? 0) > 0 ? 'Incomplete' : 'Completed';
    }

    public static function goodQty(object $item): int
    {
        return max(0, (int) ($item->receiving_report_item_quantity ?? 0) - self::damagedQty($item));
    }

    public static function damagedQty(object $item): int
    {
        $qty = max(0, (int) ($item->receiving_report_item_quantity ?? 0));
        $damaged = min($qty, max(0, (int) ($item->receiving_report_item_damaged_qty ?? 0)));
        if ($damaged === 0 && in_array($item->receiving_report_item_condition ?? null, ['bad_order', 'bad'], true)) {
            return $qty;
        }

        return $damaged;
    }

    /**
     * @param  array<int, int>  $rootRrIds
     * @return array<int, int> RR id => unresolved back order count
     */
    public static function unresolvedCounts(array $rootRrIds): array
    {
        $rootRrIds = array_values(array_unique(array_filter(array_map('intval', $rootRrIds))));
        if ($rootRrIds === [] || ! self::supported()) {
            return [];
        }

        return DB::table(self::TABLE)
            ->whereIn('back_order_root_receiving_report_id', $rootRrIds)
            ->whereIn('back_order_status', self::UNRESOLVED)
            ->groupBy('back_order_root_receiving_report_id')
            ->select('back_order_root_receiving_report_id', DB::raw('COUNT(*) as aggregate'))
            ->pluck('aggregate', 'back_order_root_receiving_report_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    public static function forRoot(int $rootRrId): Collection
    {
        if (! self::supported()) {
            return collect();
        }

        return DB::table(self::TABLE)
            ->where('back_order_root_receiving_report_id', $rootRrId)
            ->orderBy('back_order_id')
            ->get();
    }

    /**
     * Per original RR row (not a replacement row): good units and cost including verified replacement rows,
     * plus refunds and cash notes from its back orders — used for the liquidation prefill.
     *
     * @return array<int, array{good: int, amount: float, refund: float, notes: array<int, string>}>
     */
    public static function lineTotals(int $rrId): array
    {
        $items = DB::table('receiving_report_items_table')
            ->where('receiving_report_id', $rrId)
            ->orderBy('receiving_report_item_id')
            ->get()
            ->keyBy('receiving_report_item_id');
        $backOrders = self::forRoot($rrId)->keyBy('back_order_id');

        $originOf = function (int $itemId) use ($items, $backOrders): ?int {
            for ($guard = 0; $guard < 20; $guard++) {
                $item = $items->get($itemId);
                if (! $item) {
                    return null;
                }
                $parent = $backOrders->get((int) ($item->receiving_report_item_back_order_id ?? 0));
                if (! $parent) {
                    return (int) $item->receiving_report_item_id;
                }
                $itemId = (int) $parent->back_order_receiving_report_item_id;
            }

            return null;
        };

        $totals = [];
        foreach ($items as $item) {
            $origin = $originOf((int) $item->receiving_report_item_id);
            if (! $origin) {
                continue;
            }
            $totals[$origin] ??= ['good' => 0, 'amount' => 0.0, 'refund' => 0.0, 'notes' => []];
            $good = self::goodQty($item);
            $totals[$origin]['good'] += $good;
            $totals[$origin]['amount'] += $good * (float) ($item->receiving_report_item_unit_price ?? 0);
            if ($origin !== (int) $item->receiving_report_item_id && $good > 0) {
                $supplier = trim((string) ($item->receiving_report_item_supplier_name ?? ''));
                $totals[$origin]['notes'][] = $good.' replacement'.($supplier !== '' ? ' from '.$supplier : '')
                    .' @ ₱'.number_format((float) ($item->receiving_report_item_unit_price ?? 0), 2);
            }
        }

        foreach ($backOrders as $bo) {
            $origin = $originOf((int) $bo->back_order_receiving_report_item_id);
            if (! $origin) {
                continue;
            }
            $totals[$origin] ??= ['good' => 0, 'amount' => 0.0, 'refund' => 0.0, 'notes' => []];
            if ($bo->back_order_refund_amount !== null) {
                $totals[$origin]['refund'] += (float) $bo->back_order_refund_amount;
            }
            if (filled($bo->back_order_cash_note ?? null)) {
                $totals[$origin]['notes'][] = $bo->back_order_cash_note;
            }
        }

        return $totals;
    }

    /**
     * Cash advance only: compare the money available for the back-ordered units with what the replacement cost.
     *
     * @return array{0: ?float, 1: ?string} [difference, note]
     */
    public static function cashDifference(object $bo, int $quantity, float $unitPrice): array
    {
        if ($bo->back_order_payment_path !== ProcurementPaymentPath::CASH_ADVANCE) {
            return [null, null];
        }

        $cost = round($quantity * $unitPrice, 2);
        if ($bo->back_order_refund_amount !== null) {
            $available = (float) $bo->back_order_refund_amount;
            $source = 'refund of ₱'.number_format($available, 2);
        } elseif ($bo->back_order_type === 'short') {
            $available = round((int) $bo->back_order_quantity * (float) $bo->back_order_unit_price, 2);
            $source = 'unspent ₱'.number_format($available, 2).' for the missing items';
        } else {
            $available = 0.0;
            $source = 'no refund recorded';
        }

        $difference = round($available - $cost, 2);
        $note = match (true) {
            $difference > 0 => 'Replacement cost ₱'.number_format($cost, 2).' vs '.$source.': ₱'.number_format($difference, 2).' remaining – return to cashier.',
            $difference < 0 => 'Replacement cost ₱'.number_format($cost, 2).' vs '.$source.': additional ₱'.number_format(abs($difference), 2).' needed from cashier.',
            default => 'Replacement cost ₱'.number_format($cost, 2).' matches the '.$source.'.',
        };

        return [$difference, $note];
    }

    /**
     * @return array{atp_id: ?int, rfc_id: ?int, payment_path: ?string, atp_supplier_id: ?int}
     */
    public static function fundingContext(object $rr): array
    {
        $rfcId = (int) ($rr->receiving_report_request_check_id ?? 0) ?: null;
        $atpId = (int) ($rr->receiving_report_atp_id ?? 0) ?: null;
        $rfc = $rfcId ? DB::table('request_check_table')->where('request_check_id', $rfcId)->first() : null;
        if (! $atpId && $rfc) {
            $atpId = (int) ($rfc->request_check_authority_purchase_id ?? 0) ?: null;
        }
        $atp = $atpId ? DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first() : null;

        return [
            'atp_id' => $atpId,
            'rfc_id' => $rfcId,
            'payment_path' => ($atp->authority_purchase_payment_path ?? null) ?: ($rfc->request_check_funding_type ?? null),
            'atp_supplier_id' => (int) ($atp->authority_purchase_supplier_id ?? 0) ?: null,
        ];
    }

    /**
     * First validation error for back order attachments (images, PDF, Word, Excel), or null.
     */
    public static function fileValidationError(Request $request, string $field, bool $required = false, int $alreadyStored = 0): ?string
    {
        $validator = Validator::make($request->all(), [
            $field => [$required ? 'required' : 'nullable', 'array', 'max:'.max(0, self::FILE_MAX - $alreadyStored)],
            $field.'.*' => ['file', 'mimes:'.self::FILE_MIMES, 'max:'.self::FILE_MAX_KB],
        ], [
            $field.'.required' => 'Attach at least one proof file (image, PDF, Word or Excel).',
            $field.'.max' => 'You can keep up to '.self::FILE_MAX.' files.',
            $field.'.*.mimes' => 'Attachments must be an image, PDF, Word or Excel file.',
            $field.'.*.max' => 'Each attachment must be 10 MB or smaller.',
            $field.'.*.uploaded' => 'A file failed to upload. Please try a smaller file.',
        ]);

        return $validator->fails() ? $validator->errors()->first() : null;
    }

    /**
     * @return array<int, array{path: string, name: string}>
     */
    public static function storeFiles(Request $request, string $field, string $directory): array
    {
        $files = $request->file($field, []);
        $files = array_values(array_filter(is_array($files) ? $files : [$files], fn ($file) => $file instanceof UploadedFile && $file->isValid()));
        $stored = [];
        try {
            foreach (array_slice($files, 0, self::FILE_MAX) as $file) {
                $path = $file->storeAs($directory, $file->hashName(), 'public');
                if (! $path) {
                    throw new \RuntimeException('Unable to save an attachment.');
                }
                $stored[] = ['path' => $path, 'name' => mb_substr((string) $file->getClientOriginalName(), 0, 150)];
            }
        } catch (\Throwable $e) {
            self::deleteFiles($stored);
            throw new \RuntimeException('Unable to save an attachment. Please try again.');
        }

        return $stored;
    }

    /**
     * @return array<int, array{path: string, name: string}>
     */
    public static function decodeFiles($value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? array_values(array_filter($decoded, fn ($file) => is_array($file) && ! empty($file['path'])))
            : [];
    }

    public static function encodeFiles(array $files): ?string
    {
        return $files === [] ? null : json_encode(array_values($files));
    }

    public static function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            try {
                Storage::disk('public')->delete((string) ($file['path'] ?? ''));
            } catch (\Throwable $e) {
            }
        }
    }

    public static function isImage(array $file): bool
    {
        return in_array(strtolower(pathinfo((string) ($file['path'] ?? ''), PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }

    /**
     * Nothing has been recorded on this back order yet, so it may follow the RR row counts.
     */
    private static function untouched(object $bo): bool
    {
        return in_array($bo->back_order_status, [self::STATUS_OPEN, self::STATUS_WAITING_RESTOCK], true)
            && empty($bo->back_order_replacement_item_id)
            && $bo->back_order_refund_amount === null;
    }

    private static function rowRemarks(object $item, string $type): ?string
    {
        $remarks = $type === 'damaged'
            ? ($item->receiving_report_item_damage_remarks ?? null)
            : ($item->receiving_report_item_condition_remarks ?? null);

        return filled($remarks) ? (string) $remarks : null;
    }

    private static function supplierName(object $item, object $rr): ?string
    {
        foreach ([$item->receiving_report_item_supplier_name ?? null, $rr->receiving_report_received_from ?? null] as $name) {
            if (filled($name)) {
                return mb_substr(trim((string) $name), 0, 255);
            }
        }

        return null;
    }
}
