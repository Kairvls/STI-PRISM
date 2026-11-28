<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Adds several delivered RR lines (from one or more Receiving Reports) to inventory in one save.
 * New deliveries always enter as Good; each line keeps its RR, supplier, cost and lot link.
 */
class RrLineIntake
{
    public const MAX_LINES = 50;

    public static function validate(array $input): array
    {
        return Validator::make($input, [
            'storage_room_id' => 'required|integer|min:1',
            'deploy_room_id' => 'nullable|integer|min:0',
            'warranty' => 'required|date',
            'useful_life_years' => 'required|integer|min:1|max:'.EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS,
            'lines' => 'required|array|min:1|max:'.self::MAX_LINES,
            'lines.*.receiving_report_item_id' => 'required|integer|min:1|distinct',
            'lines.*.name' => 'required|string|max:'.InputLimits::NAME,
            'lines.*.category_id' => 'required|integer|min:1',
            'lines.*.tracking' => 'required|in:Bulk,Individual',
            'lines.*.quantity' => 'required|integer|min:1|max:'.min(200, InputLimits::QTY),
            'lines.*.brand' => 'required|string|max:'.InputLimits::BRAND,
            'lines.*.model' => 'required|string|max:'.InputLimits::MODEL,
            'lines.*.warranty' => 'nullable|date',
            'lines.*.useful_life_years' => 'nullable|integer|min:1|max:'.EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS,
            'lines.*.storage_room_id' => 'nullable|integer|min:1',
            'lines.*.replaces_equipment_id' => 'nullable|integer|min:1',
            'lines.*.units.*.warranty' => 'required|date',
            'lines.*.units.*.purchase_date' => 'nullable|date|before_or_equal:today',
            'lines.*.units.*.cost' => 'required|numeric|min:0|max:'.InputLimits::COST,
            'lines.*.units.*.useful_life_years' => 'required|integer|min:1|max:'.EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS,
            'lines.*.units.*.brand' => 'nullable|string|max:255',
            'lines.*.units.*.model' => 'nullable|string|max:255',
            'lines.*.asset_tag' => 'nullable|string|max:255',
            'lines.*.units' => 'nullable|array|max:200',
            'lines.*.units.*.asset_tag' => 'required|string|max:255',
            'lines.*.units.*.serial' => 'required|string|max:'.\App\Support\InputLimits::SERIAL,
            'lines.*.units.*.deploy_room_id' => 'nullable|integer|min:0',
            'lines.*.units.*.custodian_id' => 'nullable|integer|min:0',
            'lines.*.units.*.image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ], [
            'lines.*.units.*.image.image' => 'Each unit photo must be an image.',
            'lines.*.units.*.image.max' => 'Each unit photo must be 5 MB or smaller.',
            'lines.*.brand.required' => 'Brand name is required for each item.',
            'lines.*.model.required' => 'Model is required for each item.',
            'lines.*.units.*.asset_tag.required' => 'Asset tag is required for each unit.',
            'lines.*.units.*.serial.required' => 'Serial number is required for each unit.',
            'lines.*.units.*.warranty.required' => 'Warranty expiration is required for each unit.',
            'lines.*.units.*.useful_life_years.required' => 'Useful lifespan is required for each unit.',
            'lines.*.units.*.cost.required' => 'Cost is required for each unit.',
            'warranty.required' => 'Warranty expiration is required.',
            'useful_life_years.required' => 'Useful lifespan is required.',
        ], [
            'storage_room_id' => 'storage room',
            'lines.*.name' => 'equipment name',
            'lines.*.category_id' => 'category',
            'lines.*.quantity' => 'quantity',
            'lines.*.brand' => 'brand name',
            'lines.*.model' => 'model',
            'useful_life_years' => 'useful lifespan',
            'warranty' => 'warranty expiration',
            'lines.*.units.*.asset_tag' => 'asset tag',
            'lines.*.units.*.serial' => 'serial number',
            'lines.*.units.*.warranty' => 'warranty expiration',
            'lines.*.units.*.useful_life_years' => 'useful lifespan',
            'lines.*.units.*.cost' => 'cost',
        ])->validate();
    }

    /**
     * Loads the non-storage rooms equipment may be deployed to, keyed by room id.
     *
     * @param  array<int, int|string|null>  $roomIds
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function deployRooms(array $roomIds)
    {
        $ids = collect($roomIds)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rooms = DB::table('rooms_table')
            ->whereIn('room_id', $ids->all())
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where('room_is_archived', false)
            )
            ->get()
            ->keyBy(fn ($room) => (int) $room->room_id);

        foreach ($ids as $id) {
            $room = $rooms->get($id);
            if (! $room || RoomCategories::isStorageType($room->room_type ?? null)) {
                throw ValidationException::withMessages(['deploy_room_id' => 'Choose a classroom, lab, or office to deploy to.']);
            }
        }

        return $rooms;
    }

    /**
     * @param  array<int, string>  $roomNames
     */
    public static function deployNote(array $roomNames): string
    {
        $names = array_values(array_unique(array_filter($roomNames)));
        if (! $names) {
            return '';
        }
        if (count($names) === 1) {
            return ' Deployed to '.$names[0].'.';
        }

        return ' Deployed to '.count($names).' rooms ('.implode(', ', $names).').';
    }

    /**
     * @param  (callable(mixed): ?string)|null  $storeImage
     * @return array{created: int, lines: int, deploy_rooms: array<int, string>, last_id: int}
     */
    public static function store(array $data, ?callable $storeImage = null): array
    {
        $storageRoom = DB::table('rooms_table')->where('room_id', (int) $data['storage_room_id'])->first();
        if (! $storageRoom || ! RoomCategories::isStorageType($storageRoom->room_type ?? null)) {
            throw ValidationException::withMessages(['storage_room_id' => 'Choose the storage room these items are added to.']);
        }

        $defaultDeployId = (int) ($data['deploy_room_id'] ?? 0);
        $roomIds = [$defaultDeployId];
        foreach ($data['lines'] as $line) {
            foreach ($line['units'] ?? [] as $unit) {
                $roomIds[] = $unit['deploy_room_id'] ?? 0;
            }
        }
        $deployRooms = self::deployRooms($roomIds);

        $categoryIds = DB::table('equipment_categories_table')->pluck('equipment_category_id')->map(fn ($id) => (int) $id)->flip();
        $pending = ReceivableStockLines::pending()->keyBy(fn ($line) => (int) $line->receiving_report_item_id);

        $plan = [];
        foreach (array_values($data['lines']) as $i => $line) {
            $rrLine = $pending->get((int) $line['receiving_report_item_id']);
            if (! $rrLine) {
                throw ValidationException::withMessages(["lines.$i.receiving_report_item_id" => 'An RR line you selected is no longer available or is already in inventory. Refresh the list and try again.']);
            }
            if (! $categoryIds->has((int) $line['category_id'])) {
                throw ValidationException::withMessages(["lines.$i.category_id" => 'Choose a valid category for '.$rrLine->article.'.']);
            }
            $rrAvailableQty = max(1, (int) $rrLine->remaining_qty);
            $quantity = (int) $line['quantity'];
            if ($quantity !== $rrAvailableQty) {
                throw ValidationException::withMessages(["lines.$i.quantity" => $rrLine->article.' on '.$rrLine->rr_number.' must use the remaining available quantity ('.$rrAvailableQty.').']);
            }
            $quantity = $rrAvailableQty;

            $categoryId = (int) $line['category_id'];
            if (
                ($line['tracking'] ?? '') === EquipmentTrackingModes::BULK
                && EquipmentTrackingModes::requiresIndividualForCategory($categoryId)
            ) {
                throw ValidationException::withMessages([
                    "lines.$i.tracking" => $rrLine->article.' is Computer Equipment and must be stocked as Individual units (one identity per piece). Use quantity to add many.',
                ]);
            }

            $count = $line['tracking'] === 'Bulk' ? 1 : $quantity;
            $units = array_slice(array_values($line['units'] ?? []), 0, $count);
            if ($line['tracking'] === 'Bulk' && ! $units) {
                $units = [['asset_tag' => $line['asset_tag'] ?? null]];
            }
            $units = array_pad($units, $count, []);
            foreach ($units as $u => $unit) {
                $roomId = (int) ($unit['deploy_room_id'] ?? 0) ?: $defaultDeployId;
                $custodianId = (int) ($unit['custodian_id'] ?? 0);
                if ($custodianId > 0 && ($line['tracking'] === 'Bulk' || $roomId < 1)) {
                    throw ValidationException::withMessages(["lines.$i.units.$u.custodian_id" => $line['tracking'] === 'Bulk'
                        ? 'Bulk items stay with the room. Only individually tracked units can be assigned to a person.'
                        : 'Deploy the unit to a room before assigning it to a person.']);
                }
                $units[$u] = [
                    'custodian_id' => $custodianId,
                    'tag' => $unit['asset_tag'] ?? null,
                    'serial' => $unit['serial'] ?? null,
                    'brand' => self::clean($unit['brand'] ?? null) ?? self::clean($line['brand'] ?? null),
                    'model' => self::clean($unit['model'] ?? null) ?? self::clean($line['model'] ?? null),
                    'warranty' => self::clean($unit['warranty'] ?? null)
                        ?? self::clean($line['warranty'] ?? null)
                        ?? self::clean($data['warranty'] ?? null),
                    'purchase_date' => self::clean($unit['purchase_date'] ?? null),
                    'cost' => self::clean($unit['cost'] ?? null),
                    'useful_life_years' => filled($unit['useful_life_years'] ?? null)
                        ? (int) $unit['useful_life_years']
                        : null,
                    'room' => $roomId > 0 ? $deployRooms->get($roomId) : null,
                    'image' => $unit['image'] ?? null,
                ];
            }

            $plan[] = ['index' => $i, 'line' => $line, 'rr' => $rrLine, 'units' => $units];
        }

        self::assertUniqueTags($plan);

        $acquisition = EquipmentAcquisition::ready()
            ? ['equipment_acquisition_source' => EquipmentAcquisition::PROCUREMENT]
            : [];
        $usefulLife = ($data['useful_life_years'] ?? '') !== '' ? (int) $data['useful_life_years'] : null;
        $userId = Auth::id();
        $result = ['created' => 0, 'lines' => count($plan), 'deploy_rooms' => [], 'assigned' => 0, 'documents' => [], 'last_id' => 0];
        $hasTransferHistory = Schema::hasTable('equipment_transfer_history_table');

        DB::transaction(function () use ($plan, $storageRoom, $acquisition, $usefulLife, $userId, $storeImage, $hasTransferHistory, &$result) {
            $byCustodian = [];
            foreach ($plan as $entry) {
                $line = $entry['line'];
                $rr = $entry['rr'];
                $isBulk = $line['tracking'] === 'Bulk';
                $quantity = (int) $line['quantity'];
                $lineLife = filled($line['useful_life_years'] ?? null) ? (int) $line['useful_life_years'] : $usefulLife;
                $createdIds = [];

                $lineStorageRoomId = ! empty($line['storage_room_id'])
                    ? (int) $line['storage_room_id']
                    : (int) $storageRoom->room_id;

                foreach ($entry['units'] as $unit) {
                    $payload = array_merge([
                        'equipment_category_id' => (int) $line['category_id'],
                        'equipment_room_id' => $lineStorageRoomId,
                        'equipment_asset_tag' => self::clean($unit['tag']),
                        'equipment_name' => trim((string) $line['name']),
                        'equipment_brand_name' => $unit['brand'],
                        'equipment_model' => $unit['model'],
                        'equipment_serial_number' => self::clean($unit['serial']),
                        'equipment_image' => ($storeImage && $unit['image']) ? $storeImage($unit['image']) : null,
                        'equipment_quantity' => $isBulk ? $quantity : 1,
                        'equipment_tracking_mode' => $isBulk ? 'Bulk' : 'Individual',
                        'equipment_condition_status' => 'Good',
                        'equipment_inventory_status' => 'Active',
                        'equipment_purchase_date' => $unit['purchase_date']
                            ?? ($rr->purchase_date ? substr((string) $rr->purchase_date, 0, 10) : null),
                        'equipment_acquired_date' => now()->toDateString(),
                        'equipment_purchase_cost' => $unit['cost'] ?? $rr->unit_cost,
                        'equipment_warranty_expiration' => $unit['warranty'],
                        'equipment_useful_life_years' => $unit['useful_life_years'] ?? $lineLife,
                        'equipment_supplier_id' => $rr->supplier_id ? (int) $rr->supplier_id : null,
                        'equipment_stocked_by' => $userId,
                        'equipment_receiving_report_item_id' => (int) $rr->receiving_report_item_id,
                        'equipment_stock_lot_code' => 'RR'.$rr->receiving_report_id.'-L'.$rr->receiving_report_item_id,
                        'equipment_placement_zone' => 'Holding',
                        'equipment_current_location' => 'Holding',
                        'equipment_position_x' => 50,
                        'equipment_position_y' => 90,
                        'equipment_created_at' => now(),
                    ], $acquisition);

                    $equipmentId = (int) DB::table('equipment_table')->insertGetId(
                        collect($payload)->filter(fn ($value, $column) => Schema::hasColumn('equipment_table', $column))->all()
                    );
                    EquipmentQrCodes::assignIfEligible($equipmentId);
                    EquipmentStockLedger::recordReceipt(
                        $equipmentId,
                        (int) ($payload['equipment_quantity'] ?? 1),
                        (int) ($payload['equipment_room_id'] ?? 0) ?: null,
                        (int) ($payload['equipment_receiving_report_item_id'] ?? 0) ?: null
                    );
                    $createdIds[] = $equipmentId;

                    if ($unit['room']) {
                        $toRoomId = (int) $unit['room']->room_id;
                        if ($hasTransferHistory) {
                            DB::table('equipment_transfer_history_table')->insert([
                                'equipment_id' => $equipmentId,
                                'from_room_id' => $lineStorageRoomId,
                                'to_room_id' => $toRoomId,
                                'transferred_by' => $userId,
                                'remarks' => 'Deployed on intake',
                                'created_at' => now(),
                            ]);
                        }
                        DB::table('equipment_table')->where('equipment_id', $equipmentId)->update(['equipment_room_id' => $toRoomId]);
                        $result['deploy_rooms'][] = (string) $unit['room']->room_name;
                        if ($unit['custodian_id'] > 0) {
                            $byCustodian[$unit['custodian_id']][] = $equipmentId;
                        }
                    }
                }

                if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')) {
                    DB::table('receiving_report_items_table')
                        ->where('receiving_report_item_id', (int) $rr->receiving_report_item_id)
                        ->where(fn ($q) => $q->whereNull('receiving_report_item_equipment_id')->orWhere('receiving_report_item_equipment_id', 0))
                        ->update(['receiving_report_item_equipment_id' => $createdIds[0]]);
                }

                $replacesId = ! empty($line['replaces_equipment_id'])
                    ? (int) $line['replaces_equipment_id']
                    : ReceivableStockImporter::resolveReplacedEquipmentIdFromRr((int) $rr->receiving_report_id);
                if ($replacesId > 0 && Schema::hasColumn('equipment_table', 'equipment_replaces_id')) {
                    DB::table('equipment_table')->whereIn('equipment_id', $createdIds)->update(['equipment_replaces_id' => $replacesId]);
                    if (Schema::hasColumn('equipment_table', 'equipment_replaced_by_id')) {
                        DB::table('equipment_table')->where('equipment_id', $replacesId)->update(['equipment_replaced_by_id' => $createdIds[0]]);
                    }
                }

                $result['created'] += count($createdIds);
                $result['last_id'] = (int) end($createdIds);
            }

            $issued = self::issueToCustodians($byCustodian);
            $result['assigned'] = $issued['assigned'];
            $result['documents'] = $issued['documents'];
        });

        return $result;
    }

    /**
     * One property-assignment document per person. Any blocked item rolls back the whole intake.
     *
     * @param  array<int, array<int, int>>  $byCustodian
     * @return array{assigned: int, documents: array<int, string>}
     */
    public static function issueToCustodians(array $byCustodian, string $notes = 'Issued on intake'): array
    {
        $out = ['assigned' => 0, 'documents' => []];
        foreach ($byCustodian as $custodianId => $ids) {
            try {
                $issued = PropertyAssignments::issueMany($ids, (int) $custodianId, ['notes' => $notes]);
            } catch (\Illuminate\Database\QueryException $e) {
                throw $e;
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages(['custodian_id' => $e->getMessage()]);
            }
            $out['assigned'] += $issued->count();
            if ($doc = $issued->first()?->assignment_document_no) {
                $out['documents'][] = $doc;
            }
        }

        return $out;
    }

    private static function assertUniqueTags(array $plan): void
    {
        $seen = [];
        foreach ($plan as $entry) {
            foreach ($entry['units'] as $unit) {
                $value = trim((string) ($unit['tag'] ?? ''));
                if ($value === '') {
                    continue;
                }
                $key = mb_strtolower($value);
                if (isset($seen[$key])) {
                    throw ValidationException::withMessages(["lines.{$entry['index']}.asset_tag" => 'Asset tag "'.$value.'" is used twice.']);
                }
                $seen[$key] = true;

                $taken = DB::table('equipment_table')
                    ->whereRaw('LOWER(equipment_asset_tag) = ?', [$key])
                    ->whereNotIn('equipment_inventory_status', ['Disposed'])
                    ->exists();
                if ($taken) {
                    throw ValidationException::withMessages(["lines.{$entry['index']}.asset_tag" => 'Asset tag "'.$value.'" is already in use.']);
                }
            }
        }
    }

    private static function clean($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
