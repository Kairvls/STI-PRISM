<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Several different equipment types in one intake: shared acquisition source,
 * one line per type, and a destination room per line or per individual unit.
 * Everything lands in storage first; deployed units get a transfer record.
 */
class EquipmentBatchIntake
{
    public const MAX_LINES = 50;

    public const MAX_UNITS = 500;

    public static function validate(array $input): array
    {
        $sourceRule = EquipmentAcquisition::ready()
            ? 'required|string|in:'.implode(',', array_keys(EquipmentAcquisition::SOURCES))
            : 'nullable|string';

        $validator = Validator::make($input, [
            'storage_room_id' => 'required|integer|min:1',
            'source.acquisition_source' => $sourceRule,
            'source.acquisition_supplier' => 'nullable|string|max:40',
            'source.supplier_name' => 'nullable|string|'.InputLimits::name(),
            'source.reference_number' => 'nullable|string|max:120',
            'source.notes' => 'nullable|string|'.InputLimits::notes(),
            'source.purchase_date' => 'nullable|date',
            'source.received_date' => 'nullable|date',
            'lines' => 'required|array|min:1|max:'.self::MAX_LINES,
            'lines.*.name' => 'required|string|'.InputLimits::name(),
            'lines.*.category_id' => 'required|integer|min:1',
            'lines.*.tracking' => 'required|in:Bulk,Individual',
            'lines.*.quantity' => 'required|integer|min:1|max:'.min(200, InputLimits::QTY),
            'lines.*.condition' => 'nullable|'.EquipmentConditions::ruleIn(),
            'lines.*.brand' => 'nullable|string|'.InputLimits::name(),
            'lines.*.model' => 'nullable|string|'.InputLimits::name(),
            'lines.*.cost' => 'nullable|numeric|min:0|max:'.InputLimits::COST,
            'lines.*.warranty' => 'nullable|date',
            'lines.*.useful_life_years' => 'nullable|integer|min:1|max:50',
            'lines.*.borrowable' => 'nullable|boolean',
            'lines.*.destination_room_id' => 'nullable|integer|min:0',
            'lines.*.custodian_id' => 'nullable|integer|min:0',
            'lines.*.asset_tag' => 'nullable|string|max:'.InputLimits::ASSET_TAG,
            'lines.*.serial' => 'nullable|string|max:'.InputLimits::SERIAL,
            'lines.*.units' => 'nullable|array|max:200',
            'lines.*.units.*.asset_tag' => 'nullable|string|max:'.InputLimits::ASSET_TAG,
            'lines.*.units.*.serial' => 'nullable|string|max:'.InputLimits::SERIAL,
            'lines.*.units.*.room_id' => 'nullable|integer|min:0',
            'lines.*.units.*.warranty' => 'nullable|date',
            'lines.*.units.*.custodian_id' => 'nullable|integer|min:0',
            'lines.*.units.*.brand' => 'nullable|string|'.InputLimits::name(),
            'lines.*.units.*.model' => 'nullable|string|'.InputLimits::name(),
            'lines.*.units.*.image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ], [], [
            'storage_room_id' => 'storage room',
            'source.acquisition_source' => 'acquisition source',
            'lines.*.name' => 'equipment name',
            'lines.*.category_id' => 'classification',
            'lines.*.tracking' => 'tracking mode',
            'lines.*.quantity' => 'quantity',
            'lines.*.cost' => 'cost per unit',
            'lines.*.warranty' => 'warranty expiration',
            'lines.*.useful_life_years' => 'useful lifespan',
            'lines.*.units.*.warranty' => 'unit warranty expiration',
            'lines.*.units.*.image' => 'unit photo',
        ]);

        return $validator->validate();
    }

    /**
     * @return array{created: int, lines: int, assigned: int, documents: array<int, string>, rooms: array<string, int>, last_id: int}
     */
    public static function store(array $data, ?callable $storeImage = null): array
    {
        $rooms = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where(fn ($q) => $q->whereNull('room_is_archived')->orWhere('room_is_archived', false))
            )
            ->get(['room_id', 'room_name', 'room_type'])
            ->keyBy('room_id');

        $storageRoomId = (int) $data['storage_room_id'];
        $storageRoom = $rooms->get($storageRoomId);
        if (! $storageRoom || ! RoomCategories::isStorageType($storageRoom->room_type ?? null)) {
            throw ValidationException::withMessages(['storage_room_id' => 'Choose the storage room these items are added to.']);
        }

        $deployRoom = function (int $roomId, string $errorKey) use ($rooms, $storageRoomId) {
            if ($roomId < 1 || $roomId === $storageRoomId) {
                return null;
            }
            $room = $rooms->get($roomId);
            if (! $room || RoomCategories::isStorageType($room->room_type ?? null)) {
                throw ValidationException::withMessages([$errorKey => 'Choose a classroom, lab, or office, or keep it in storage.']);
            }

            return $room;
        };

        $categoryIds = DB::table('equipment_categories_table')->pluck('equipment_category_id')->map(fn ($id) => (int) $id)->flip();

        $plan = [];
        $totalUnits = 0;
        foreach (array_values($data['lines']) as $i => $line) {
            if (! $categoryIds->has((int) $line['category_id'])) {
                throw ValidationException::withMessages(["lines.$i.category_id" => 'Choose a valid category.']);
            }

            $tracking = $line['tracking'];
            $quantity = (int) $line['quantity'];
            $lineRoom = $deployRoom((int) ($line['destination_room_id'] ?? 0), "lines.$i.destination_room_id");

            if ($tracking === 'Bulk') {
                $unit = array_values($line['units'] ?? [])[0] ?? [];
                $unit['asset_tag'] = self::clean($unit['asset_tag'] ?? null) ?? ($line['asset_tag'] ?? null);
                $unit['serial'] = self::clean($unit['serial'] ?? null) ?? ($line['serial'] ?? null);
                $unitRoomId = array_key_exists('room_id', $unit) && $unit['room_id'] !== null
                    ? (int) $unit['room_id']
                    : (int) ($lineRoom->room_id ?? 0);
                $units = [self::unitPlan($line, $unit, $deployRoom($unitRoomId, "lines.$i.units.0.room_id"))];
                $totalUnits += 1;
            } else {
                $rawUnits = array_values($line['units'] ?? []);
                if ($rawUnits === []) {
                    $rawUnits = array_fill(0, $quantity, []);
                    if ($quantity === 1) {
                        $rawUnits[0] = ['asset_tag' => $line['asset_tag'] ?? null, 'serial' => $line['serial'] ?? null];
                    }
                }
                if (count($rawUnits) !== $quantity) {
                    throw ValidationException::withMessages(["lines.$i.quantity" => 'Unit rows must match the quantity.']);
                }
                $units = [];
                foreach ($rawUnits as $u => $unit) {
                    $unitRoomId = array_key_exists('room_id', $unit) && $unit['room_id'] !== null
                        ? (int) $unit['room_id']
                        : (int) ($lineRoom->room_id ?? 0);
                    $units[] = self::unitPlan($line, $unit, $deployRoom($unitRoomId, "lines.$i.units.$u.room_id"));
                }
                $totalUnits += count($units);
            }

            foreach ($units as $u => $unit) {
                if ($unit['custodian_id'] < 1) {
                    continue;
                }
                if ($tracking !== 'Individual') {
                    throw ValidationException::withMessages(["lines.$i.units.$u.custodian_id" => 'Bulk items stay with the room. Only individually tracked units can be assigned to a person.']);
                }
                if ($unit['room'] === null) {
                    throw ValidationException::withMessages(["lines.$i.units.$u.custodian_id" => 'Deploy the unit to a room before assigning it to a person.']);
                }
            }

            $custodianId = (int) ($line['custodian_id'] ?? 0);
            if ($custodianId > 0) {
                if ($tracking !== 'Individual') {
                    throw ValidationException::withMessages(["lines.$i.custodian_id" => 'Only individually tracked equipment can be assigned to a person.']);
                }
                if (! collect($units)->contains(fn ($unit) => $unit['room'] !== null)) {
                    throw ValidationException::withMessages(["lines.$i.custodian_id" => 'Deploy at least one unit to a room before assigning a person.']);
                }
            }

            $plan[] = ['index' => $i, 'line' => $line, 'units' => $units, 'custodian_id' => $custodianId];
        }

        if ($totalUnits > self::MAX_UNITS) {
            throw ValidationException::withMessages(['lines' => 'A batch can create at most '.self::MAX_UNITS.' records. Split it into smaller batches.']);
        }

        self::assertUniqueIdentifiers($plan);

        $source = $data['source'] ?? [];
        $acquisitionColumns = EquipmentAcquisition::manualColumns([
            'equipment_acquisition_source' => $source['acquisition_source'] ?? null,
            'acquisition_supplier' => $source['acquisition_supplier'] ?? null,
            'equipment_supplier_name' => ($source['acquisition_supplier'] ?? '') === EquipmentAcquisition::OTHER_SUPPLIER
                ? ($source['supplier_name'] ?? null)
                : null,
            'equipment_reference_number' => $source['reference_number'] ?? null,
            'equipment_acquisition_notes' => $source['notes'] ?? null,
        ]);
        $purchaseDate = $source['purchase_date'] ?? null;
        $acquiredDate = $source['received_date'] ?? now()->toDateString();
        $hasStockedBy = Schema::hasColumn('equipment_table', 'equipment_stocked_by');
        $hasTransfers = Schema::hasTable('equipment_transfer_history_table');
        $hasImage = $storeImage !== null && Schema::hasColumn('equipment_table', 'equipment_image');
        $userId = Auth::id();

        $result = ['created' => 0, 'lines' => count($plan), 'assigned' => 0, 'documents' => [], 'rooms' => [], 'last_id' => 0];

        DB::transaction(function () use (
            $plan, $storageRoom, $acquisitionColumns, $purchaseDate, $acquiredDate,
            $hasStockedBy, $hasTransfers, $hasImage, $storeImage, $userId, &$result
        ) {
            $byCustodian = [];

            foreach ($plan as $entry) {
                $line = $entry['line'];
                $condition = $line['condition'] ?? EquipmentConditions::GOOD;
                $inventoryStatus = EquipmentConditions::syncInventoryStatus($condition);
                $isBulk = $line['tracking'] === 'Bulk';

                foreach ($entry['units'] as $unit) {
                    $payload = array_merge([
                        'equipment_category_id' => (int) $line['category_id'],
                        'equipment_room_id' => (int) $storageRoom->room_id,
                        'equipment_asset_tag' => self::clean($unit['asset_tag']),
                        'equipment_name' => trim((string) $line['name']),
                        'equipment_brand_name' => $unit['brand'],
                        'equipment_model' => $unit['model'],
                        'equipment_serial_number' => self::clean($unit['serial']),
                        'equipment_quantity' => $isBulk ? (int) $line['quantity'] : 1,
                        'equipment_tracking_mode' => $isBulk ? 'Bulk' : 'Individual',
                        'equipment_condition_status' => $condition,
                        'equipment_inventory_status' => $inventoryStatus,
                        'equipment_purchase_date' => $purchaseDate,
                        'equipment_acquired_date' => $acquiredDate,
                        'equipment_purchase_cost' => ($line['cost'] ?? '') !== '' ? $line['cost'] : null,
                        'equipment_warranty_expiration' => $unit['warranty'],
                        'equipment_useful_life_years' => ($line['useful_life_years'] ?? '') !== '' ? (int) $line['useful_life_years'] : null,
                        'equipment_is_borrowable' => (bool) ($line['borrowable'] ?? false),
                        'equipment_placement_zone' => 'Holding',
                        'equipment_current_location' => 'Holding',
                        'equipment_position_x' => 50,
                        'equipment_position_y' => 90,
                        'equipment_created_at' => now(),
                    ], $acquisitionColumns);
                    if ($hasStockedBy && $userId) {
                        $payload['equipment_stocked_by'] = $userId;
                    }
                    if ($hasImage && $unit['image']) {
                        $payload['equipment_image'] = $storeImage($unit['image']);
                    }

                    $equipmentId = (int) DB::table('equipment_table')->insertGetId($payload);
                    EquipmentQrCodes::assignIfEligible($equipmentId);

                    $finalRoom = $unit['room'] ?? $storageRoom;
                    if ($unit['room']) {
                        if ($hasTransfers) {
                            DB::table('equipment_transfer_history_table')->insert([
                                'equipment_id' => $equipmentId,
                                'from_room_id' => (int) $storageRoom->room_id,
                                'to_room_id' => (int) $unit['room']->room_id,
                                'transferred_by' => $userId,
                                'remarks' => 'Deployed on batch intake',
                                'created_at' => now(),
                            ]);
                        }
                        DB::table('equipment_table')
                            ->where('equipment_id', $equipmentId)
                            ->update(['equipment_room_id' => (int) $unit['room']->room_id]);

                        $unitCustodian = $unit['custodian_id'] ?: $entry['custodian_id'];
                        if ($unitCustodian > 0) {
                            $byCustodian[$unitCustodian][] = $equipmentId;
                        }
                    }

                    $result['created']++;
                    $result['last_id'] = $equipmentId;
                    $roomName = (string) $finalRoom->room_name;
                    $result['rooms'][$roomName] = ($result['rooms'][$roomName] ?? 0) + ($isBulk ? (int) $line['quantity'] : 1);
                }
            }

            foreach ($byCustodian as $custodianId => $ids) {
                try {
                    $issued = PropertyAssignments::issueMany($ids, (int) $custodianId, ['notes' => 'Issued on batch intake']);
                } catch (\Illuminate\Database\QueryException $e) {
                    throw $e;
                } catch (RuntimeException $e) {
                    $lineIndex = collect($plan)->firstWhere('custodian_id', (int) $custodianId)['index'] ?? 0;
                    throw ValidationException::withMessages(["lines.$lineIndex.custodian_id" => $e->getMessage()]);
                }
                $result['assigned'] += $issued->count();
                if ($doc = $issued->first()?->assignment_document_no) {
                    $result['documents'][] = $doc;
                }
            }
        });

        return $result;
    }

    private static function assertUniqueIdentifiers(array $plan): void
    {
        foreach (['asset_tag' => 'equipment_asset_tag', 'serial' => 'equipment_serial_number'] as $field => $column) {
            $label = $field === 'asset_tag' ? 'Asset tag' : 'Serial number';
            $seen = [];
            foreach ($plan as $entry) {
                foreach ($entry['units'] as $u => $unit) {
                    $value = trim((string) ($unit[$field] ?? ''));
                    if ($value === '') {
                        continue;
                    }
                    $key = mb_strtolower($value);
                    $errorKey = $entry['line']['tracking'] === 'Bulk'
                        ? "lines.{$entry['index']}.$field"
                        : "lines.{$entry['index']}.units.$u.$field";
                    if (isset($seen[$key])) {
                        throw ValidationException::withMessages([$errorKey => "$label \"$value\" is used twice in this batch."]);
                    }
                    $seen[$key] = true;

                    $taken = DB::table('equipment_table')
                        ->whereRaw("LOWER($column) = ?", [$key])
                        ->whereNotIn('equipment_inventory_status', ['Disposed'])
                        ->exists();
                    if ($taken) {
                        throw ValidationException::withMessages([$errorKey => "$label \"$value\" is already in use."]);
                    }
                }
            }
        }
    }

    private static function unitPlan(array $line, array $unit, ?object $room): array
    {
        $image = $unit['image'] ?? null;

        return [
            'asset_tag' => $unit['asset_tag'] ?? null,
            'serial' => $unit['serial'] ?? null,
            'brand' => self::clean($unit['brand'] ?? null) ?? self::clean($line['brand'] ?? null),
            'model' => self::clean($unit['model'] ?? null) ?? self::clean($line['model'] ?? null),
            'warranty' => self::clean($unit['warranty'] ?? null) ?? self::clean($line['warranty'] ?? null),
            'image' => $image instanceof \Illuminate\Http\UploadedFile ? $image : null,
            'room' => $room,
            'custodian_id' => (int) ($unit['custodian_id'] ?? 0),
        ];
    }

    private static function clean($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
