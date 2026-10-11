<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * How an equipment record entered campus stock, for records that did not come
 * through a Receiving Report line (donations, old inventory, Head Office transfers).
 * RR-linked records derive supplier / PO / RR from the document chain instead.
 */
class EquipmentAcquisition
{
    public const PROCUREMENT = 'procurement';

    public const SOURCES = [
        'donation' => 'Donation',
        'legacy' => 'Already owned before PaAyo (old inventory)',
        'transfer' => 'Transfer from Head Office',
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
     * Adds a physical-store supplier by name from Maintenance intake, or returns
     * the active supplier that already has that name.
     *
     * @return object{supplier_id: int, supplier_name: string, existing: bool}
     */
    public static function quickAddSupplier(string $name): object
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Type the supplier name first.']);
        }

        $existing = self::supplierOptions()
            ->first(fn ($row) => mb_strtolower($row->supplier_name) === mb_strtolower($name));
        if ($existing) {
            return (object) ['supplier_id' => (int) $existing->supplier_id, 'supplier_name' => $existing->supplier_name, 'existing' => true];
        }

        $inactive = Schema::hasTable('physical_suppliers_table')
            && DB::table('physical_suppliers_table')->whereRaw('LOWER(company_name) = ?', [mb_strtolower($name)])->exists();
        $inactive = $inactive || (Schema::hasTable('online_suppliers_table')
            && DB::table('online_suppliers_table')->whereRaw('LOWER(shop_name) = ?', [mb_strtolower($name)])->exists());
        if ($inactive) {
            throw ValidationException::withMessages(['name' => $name.' is already in the supplier list but is inactive or blacklisted. Ask the Purchaser to reactivate it.']);
        }

        $createdAt = now();
        $payload = [
            'supplier_store_type' => 'Physical Store',
            'supplier_is_active' => 1,
            'supplier_created_at' => $createdAt,
        ];
        if (Schema::hasColumn('suppliers_table', 'supplier_is_blacklisted')) {
            $payload['supplier_is_blacklisted'] = 0;
        }
        if (Schema::hasColumn('suppliers_table', 'supplier_code')) {
            $base = SupplierCode::generate('Physical Store', $name, $createdAt);
            $code = $base;
            for ($n = 2; DB::table('suppliers_table')->where('supplier_code', $code)->exists(); $n++) {
                $code = $base.'-'.str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            }
            $payload['supplier_code'] = $code;
        }

        $supplierId = DB::transaction(function () use ($payload, $name) {
            $supplierId = (int) DB::table('suppliers_table')->insertGetId($payload);
            DB::table('physical_suppliers_table')->insert([
                'supplier_id' => $supplierId,
                'company_name' => $name,
            ]);
            if (Schema::hasTable('supplier_notes_table')) {
                DB::table('supplier_notes_table')->insert([
                    'supplier_id' => $supplierId,
                    'supplier_note_user_id' => Auth::id(),
                    'supplier_note_type' => 'note',
                    'supplier_note_body' => 'Added by Maintenance while recording equipment intake. Contact details still need to be filled in.',
                    'created_at' => now(),
                ]);
            }

            return $supplierId;
        });

        return (object) ['supplier_id' => $supplierId, 'supplier_name' => $name, 'existing' => false];
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
