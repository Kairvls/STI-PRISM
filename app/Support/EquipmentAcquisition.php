<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How an equipment record entered campus stock, for records that did not come
 * through a Receiving Report line (donations, legacy items, direct purchases).
 * RR-linked records derive supplier / PO / RR from the document chain instead.
 */
class EquipmentAcquisition
{
    public const PROCUREMENT = 'procurement';

    public const SOURCES = [
        'direct_purchase' => 'Direct purchase (outside RIS / PO)',
        'donation' => 'Donation',
        'legacy' => 'Legacy (existed before PaAyo)',
        'transfer' => 'Transfer from another branch / office',
        'found' => 'Found / unrecorded',
    ];

    public const OTHER_SUPPLIER = 'other';

    public static function ready(): bool
    {
        return Schema::hasTable('equipment_table')
            && Schema::hasColumn('equipment_table', 'equipment_acquisition_source');
    }

    public static function sourceLabel(?string $source): ?string
    {
        if (! filled($source)) {
            return null;
        }

        return $source === self::PROCUREMENT
            ? 'Procurement (RIS → ATP → PO → RR)'
            : (self::SOURCES[$source] ?? ucfirst(str_replace('_', ' ', $source)));
    }

    /**
     * Active suppliers from the purchaser's supplier list.
     */
    public static function supplierOptions(): Collection
    {
        if (! Schema::hasTable('suppliers_table')) {
            return collect();
        }

        $query = DB::table('suppliers_table');
        $nameParts = [];
        if (Schema::hasTable('physical_suppliers_table')) {
            $query->leftJoin('physical_suppliers_table', 'physical_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id');
            $nameParts[] = 'physical_suppliers_table.company_name';
        }
        if (Schema::hasTable('online_suppliers_table')) {
            $query->leftJoin('online_suppliers_table', 'online_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id');
            $nameParts[] = 'online_suppliers_table.shop_name';
        }
        if ($nameParts === []) {
            return collect();
        }

        if (Schema::hasColumn('suppliers_table', 'supplier_is_active')) {
            $query->where('suppliers_table.supplier_is_active', true);
        }
        if (Schema::hasColumn('suppliers_table', 'supplier_is_blacklisted')) {
            $query->where(function ($q) {
                $q->whereNull('suppliers_table.supplier_is_blacklisted')
                    ->orWhere('suppliers_table.supplier_is_blacklisted', false);
            });
        }

        return $query
            ->select(
                'suppliers_table.supplier_id',
                DB::raw('COALESCE('.implode(', ', $nameParts).') as supplier_name')
            )
            ->get()
            ->filter(fn ($row) => filled($row->supplier_name))
            ->sortBy(fn ($row) => mb_strtolower($row->supplier_name))
            ->values();
    }

    /**
     * Equipment flagged "For Replacement" that no new unit replaces yet.
     */
    public static function replacementCandidates(): Collection
    {
        if (! Schema::hasTable('equipment_table')) {
            return collect();
        }

        return DB::table('equipment_table as e')
            ->leftJoin('rooms_table as room', 'room.room_id', '=', 'e.equipment_room_id')
            ->where('e.equipment_inventory_status', 'For Replacement')
            ->when(
                Schema::hasColumn('equipment_table', 'equipment_replaced_by_id'),
                fn ($q) => $q->whereNull('e.equipment_replaced_by_id')
            )
            ->orderBy('e.equipment_name')
            ->get(['e.equipment_id', 'e.equipment_name', 'e.equipment_asset_tag', 'room.room_name']);
    }

    /**
     * Active rooms split into storage (where intake lands) and deploy targets.
     *
     * @return array{storage: Collection, deploy: Collection}
     */
    public static function intakeRooms(): array
    {
        if (! Schema::hasTable('rooms_table')) {
            return ['storage' => collect(), 'deploy' => collect()];
        }

        $rooms = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where('room_is_archived', false)
            )
            ->orderBy('room_name')
            ->get();

        [$storage, $deploy] = $rooms->partition(
            fn ($room) => RoomCategories::isStorageType($room->room_type ?? null)
        );

        return ['storage' => $storage->values(), 'deploy' => $deploy->values()];
    }

    public static function validationRules(): array
    {
        return [
            'equipment_acquisition_source' => 'nullable|string|in:'.implode(',', array_keys(self::SOURCES)),
            'acquisition_supplier' => 'nullable|string|max:40',
            'equipment_supplier_name' => 'nullable|string|max:255',
            'equipment_reference_number' => 'nullable|string|max:120',
            'equipment_acquisition_notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Supplier id + free-text name from the "Supplier / donor" picker.
     *
     * @return array{0: ?int, 1: ?string}
     */
    public static function resolveSupplier(array $input): array
    {
        $choice = trim((string) ($input['acquisition_supplier'] ?? ''));
        $typedName = trim((string) ($input['equipment_supplier_name'] ?? ''));

        if ($choice !== '' && $choice !== self::OTHER_SUPPLIER && ctype_digit($choice)) {
            $exists = Schema::hasTable('suppliers_table')
                && DB::table('suppliers_table')->where('supplier_id', (int) $choice)->exists();
            if ($exists) {
                return [(int) $choice, null];
            }
        }

        return [null, $typedName !== '' ? $typedName : null];
    }

    /**
     * Manual acquisition columns for non-RR records.
     */
    public static function manualColumns(array $input, ?string $fallbackNotes = null): array
    {
        if (! self::ready()) {
            return [];
        }

        [$supplierId, $supplierName] = self::resolveSupplier($input);
        $notes = trim((string) ($input['equipment_acquisition_notes'] ?? ''));
        if ($notes === '' && filled($fallbackNotes)) {
            $notes = trim((string) $fallbackNotes);
        }
        $reference = trim((string) ($input['equipment_reference_number'] ?? ''));
        $source = trim((string) ($input['equipment_acquisition_source'] ?? ''));

        $columns = [
            'equipment_acquisition_source' => $source !== '' ? $source : null,
            'equipment_supplier_name' => $supplierName,
            'equipment_reference_number' => $reference !== '' ? $reference : null,
            'equipment_acquisition_notes' => $notes !== '' ? mb_substr($notes, 0, 500) : null,
        ];

        if (Schema::hasColumn('equipment_table', 'equipment_supplier_id')) {
            $columns['equipment_supplier_id'] = $supplierId;
        }

        return $columns;
    }

    /**
     * Values for the edit form's acquisition section.
     */
    public static function formPayload(object $equipment): array
    {
        $supplierId = (int) ($equipment->equipment_supplier_id ?? 0);
        $supplierName = (string) ($equipment->equipment_supplier_name ?? '');

        return [
            'source' => (string) ($equipment->equipment_acquisition_source ?? ''),
            'supplier' => $supplierId > 0 ? (string) $supplierId : ($supplierName !== '' ? self::OTHER_SUPPLIER : ''),
            'supplier_name' => $supplierName,
            'reference' => (string) ($equipment->equipment_reference_number ?? ''),
            'notes' => (string) ($equipment->equipment_acquisition_notes ?? ''),
            'linked_rr' => (int) ($equipment->equipment_receiving_report_item_id ?? 0) > 0,
        ];
    }
}
