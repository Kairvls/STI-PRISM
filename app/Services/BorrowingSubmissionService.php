<?php

namespace App\Services;

use App\Support\BorrowingDecisionMail;
use App\Support\BorrowingSlipNumber;
use App\Support\Custodians;
use App\Support\EquipmentConditions;
use App\Support\EquipmentInventoryStatuses;
use App\Support\EventNames;
use App\Support\EventTypes;
use App\Support\RoomCategories;
use App\Support\WorkflowNotifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class BorrowingSubmissionService
{
    /** Statuses that actually hold stock (approved & out). Pending requests do not block stock until approved. */
    public const RESERVED_STATUSES = ['Borrowed', 'Overdue'];

    /**
     * Active equipment still in inventory (storage), minus actually out qty.
     * Deployed equipment is excluded. Pending requests are surfaced as demand count, not deducted.
     *
     * @return list<array{id:int,name:string,room:string,roomId:int,assetTag:string,tracking:string,stock:int,available:int,pending:int}>
     */
    public function borrowableCatalog(): array
    {
        if (
            ! Schema::hasTable('equipment_table')
            || ! Schema::hasTable('borrowing_records_table')
            || ! Schema::hasTable('rooms_table')
        ) {
            return [];
        }

        $onLoanByEquipment = DB::table('borrowing_records_table')
            ->select(
                'borrowing_equipment_id',
                DB::raw('COALESCE(SUM(borrowing_quantity), 0) as on_loan_qty')
            )
            ->whereIn('borrowing_status', self::RESERVED_STATUSES)
            ->groupBy('borrowing_equipment_id')
            ->pluck('on_loan_qty', 'borrowing_equipment_id');

        // Count pending demand by equipment_id (for Bulk) and by pool key (for Individual),
        // even if a pending row currently points at a unit that was already lent out.
        $pendingByBulkId = [];
        $pendingByPool = [];
        $pendingRows = DB::table('borrowing_records_table as b')
            ->join('equipment_table as e', 'b.borrowing_equipment_id', '=', 'e.equipment_id')
            ->where('b.borrowing_status', 'Pending')
            ->get([
                'b.borrowing_equipment_id',
                'b.borrowing_from_room_id',
                'b.borrowing_quantity',
                'e.equipment_name',
                'e.equipment_room_id',
                'e.equipment_tracking_mode',
            ]);

        foreach ($pendingRows as $pRow) {
            $qty = max(1, (int) ($pRow->borrowing_quantity ?? 1));
            $tracking = (string) ($pRow->equipment_tracking_mode ?? 'Individual');
            if ($tracking === 'Bulk') {
                $eid = (int) $pRow->borrowing_equipment_id;
                $pendingByBulkId[$eid] = ($pendingByBulkId[$eid] ?? 0) + $qty;
                continue;
            }
            $roomId = (int) ($pRow->borrowing_from_room_id ?: ($pRow->equipment_room_id ?? 0));
            $key = $this->borrowPoolKey((string) ($pRow->equipment_name ?? ''), $roomId);
            $pendingByPool[$key] = ($pendingByPool[$key] ?? 0) + $qty;
        }

        $query = DB::table('equipment_table')
            ->join(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->where('equipment_inventory_status', EquipmentInventoryStatuses::ACTIVE)
            ->where(function ($query) {
                $query->whereNull('equipment_table.equipment_condition_status')
                    ->orWhereNotIn('equipment_table.equipment_condition_status', EquipmentConditions::NOT_BORROWABLE);
            })
            ->where('rooms_table.room_type', RoomCategories::STORAGE_TYPE);

        $select = [
            'equipment_table.equipment_id',
            'equipment_table.equipment_name',
            'equipment_table.equipment_quantity',
            'equipment_table.equipment_tracking_mode',
            'equipment_table.equipment_asset_tag',
            'equipment_table.equipment_room_id',
            'rooms_table.room_name',
        ];

        $rows = $query
            ->select($select)
            ->orderBy('equipment_name', 'asc')
            ->get()
            ->map(function ($item) use ($onLoanByEquipment, $pendingByBulkId, $pendingByPool) {
                $stock = max(1, (int) ($item->equipment_quantity ?? 1));
                $onLoan = (int) ($onLoanByEquipment[$item->equipment_id] ?? 0);
                $available = max(0, $stock - $onLoan);
                $tracking = (string) ($item->equipment_tracking_mode ?? 'Individual');
                $roomId = (int) ($item->equipment_room_id ?? 0);
                $pending = $tracking === 'Bulk'
                    ? (int) ($pendingByBulkId[(int) $item->equipment_id] ?? 0)
                    : (int) ($pendingByPool[$this->borrowPoolKey((string) $item->equipment_name, $roomId)] ?? 0);

                return [
                    'id' => (int) $item->equipment_id,
                    'name' => (string) $item->equipment_name,
                    'room' => (string) ($item->room_name ?? ''),
                    'roomId' => $roomId,
                    'assetTag' => (string) ($item->equipment_asset_tag ?? ''),
                    'tracking' => $tracking,
                    'stock' => $stock,
                    'available' => $available,
                    'pending' => $pending,
                ];
            })
            ->filter(fn (array $item) => $item['available'] > 0)
            ->values()
            ->all();

        return $this->presentBorrowableCatalog($rows);
    }

    /**
     * Identical individual units (same name, same stock room) are one bulk choice.
     * The borrower sets how many to take after adding that line.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function presentBorrowableCatalog(array $rows): array
    {
        $pools = [];
        $presented = [];

        foreach ($rows as $item) {
            $tracking = (string) ($item['tracking'] ?? 'Individual');
            $name = trim((string) ($item['name'] ?? ''));
            if ($tracking === 'Bulk' || $name === '') {
                $presented[] = $item;
                continue;
            }

            $key = $this->borrowPoolKey($name, (int) ($item['roomId'] ?? 0));
            $pools[$key][] = $item;
        }

        foreach ($pools as $members) {
            if (count($members) < 2) {
                $presented[] = $members[0];
                continue;
            }

            usort($members, fn (array $a, array $b) => ((int) $a['id']) <=> ((int) $b['id']));
            $first = $members[0];
            $presented[] = [
                'id' => (int) $first['id'],
                'name' => (string) $first['name'],
                'room' => (string) ($first['room'] ?? ''),
                'roomId' => (int) ($first['roomId'] ?? 0),
                'assetTag' => '',
                'tracking' => 'Bulk',
                'stock' => array_sum(array_map(fn (array $member) => (int) $member['stock'], $members)),
                'available' => array_sum(array_map(fn (array $member) => (int) $member['available'], $members)),
                'pending' => (int) ($first['pending'] ?? 0),
                'grouped' => true,
            ];
        }

        usort($presented, function (array $a, array $b) {
            $byName = strcasecmp((string) $a['name'], (string) $b['name']);

            return $byName !== 0 ? $byName : ((int) $a['id'] <=> (int) $b['id']);
        });

        return array_values($presented);
    }

    private function borrowPoolKey(string $name, int $roomId): string
    {
        $name = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? ''));

        return $name.'|'.$roomId;
    }

    /**
     * A bulk borrow of identical individual units becomes one record per unit.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    private function spreadIndividualQuantity(array $lines): array
    {
        $claimed = [];
        $expanded = [];

        foreach ($lines as $line) {
            $seed = DB::table('equipment_table')
                ->where('equipment_id', (int) ($line['equipment_id'] ?? 0))
                ->lockForUpdate()
                ->first();

            $requested = max(1, (int) ($line['quantity'] ?? 1));
            $isBulk = $seed && (string) ($seed->equipment_tracking_mode ?? 'Individual') === 'Bulk';

            if (! $seed || $isBulk) {
                $expanded[] = $line;
                continue;
            }

            $units = $this->lockedIndividualPool($seed, $claimed);
            $total = array_sum(array_column($units, 'available'));

            if ($requested > $total) {
                $name = trim((string) ($seed->equipment_name ?? ''));
                $label = $name !== '' ? $name : 'This equipment';

                throw new RuntimeException($label.' only has '.$total.' available to borrow.');
            }

            $remaining = $requested;
            foreach ($units as $unit) {
                if ($remaining < 1) {
                    break;
                }

                $take = min($remaining, (int) $unit['available']);
                if ($take < 1) {
                    continue;
                }

                $expanded[] = [
                    'equipment_id' => (int) $unit['id'],
                    'quantity' => $take,
                    'condition' => $line['condition'] ?? null,
                ];
                $claimed[(int) $unit['id']] = ($claimed[(int) $unit['id']] ?? 0) + $take;
                $remaining -= $take;
            }
        }

        return $expanded;
    }

    /**
     * Active individual units that share the seed's name and stock room.
     *
     * @param  array<int, int>  $claimed
     * @return list<array{id:int,available:int}>
     */
    private function lockedIndividualPool(object $seed, array $claimed, ?int $overrideRoomId = null): array
    {
        $roomId = $overrideRoomId !== null && $overrideRoomId > 0
            ? $overrideRoomId
            : (int) ($seed->equipment_room_id ?? 0);
        if ($roomId < 1) {
            return [];
        }
        if (Schema::hasTable('rooms_table')) {
            $roomType = DB::table('rooms_table')->where('room_id', $roomId)->value('room_type');
            if ((string) $roomType !== RoomCategories::STORAGE_TYPE) {
                return [];
            }
        }
        $poolKey = $this->borrowPoolKey((string) ($seed->equipment_name ?? ''), $roomId);

        $units = DB::table('equipment_table')
            ->where('equipment_room_id', $roomId)
            ->where('equipment_inventory_status', EquipmentInventoryStatuses::ACTIVE)
            ->where(function ($query) {
                $query->whereNull('equipment_condition_status')
                    ->orWhereNotIn('equipment_condition_status', EquipmentConditions::NOT_BORROWABLE);
            })
            ->where(function ($query) {
                $query->whereNull('equipment_tracking_mode')
                    ->orWhere('equipment_tracking_mode', '!=', 'Bulk');
            })
            ->orderBy('equipment_id')
            ->lockForUpdate()
            ->get(['equipment_id', 'equipment_name', 'equipment_quantity']);

        $pool = [];
        foreach ($units as $unit) {
            if ($this->borrowPoolKey((string) ($unit->equipment_name ?? ''), $roomId) !== $poolKey) {
                continue;
            }
            $stock = max(1, (int) ($unit->equipment_quantity ?? 1));
            $onLoan = (int) DB::table('borrowing_records_table')
                ->where('borrowing_equipment_id', $unit->equipment_id)
                ->whereIn('borrowing_status', self::RESERVED_STATUSES)
                ->sum('borrowing_quantity');
            $available = max(0, $stock - $onLoan - (int) ($claimed[(int) $unit->equipment_id] ?? 0));

            if ($available < 1) {
                continue;
            }

            $pool[] = [
                'id' => (int) $unit->equipment_id,
                'available' => $available,
            ];
        }

        return $pool;
    }

    /**
     * Borrowing is limited to equipment that is still in a storage room.
     */
    private function assertStoredInInventory(object $equipment): void
    {
        $roomType = null;
        if (! empty($equipment->equipment_room_id) && Schema::hasTable('rooms_table')) {
            $roomType = DB::table('rooms_table')
                ->where('room_id', $equipment->equipment_room_id)
                ->value('room_type');
        }

        if ((string) $roomType === RoomCategories::STORAGE_TYPE) {
            return;
        }

        $name = trim((string) ($equipment->equipment_name ?? ''));
        $label = $name !== '' ? $name : 'This equipment';

        throw new RuntimeException(
            $label.' is deployed and cannot be borrowed. Only equipment still in inventory can be borrowed.'
        );
    }

    /**
     * Under maintenance, damaged, and defective equipment cannot be borrowed.
     */
    private function assertBorrowableCondition(object $equipment): void
    {
        $condition = (string) ($equipment->equipment_condition_status ?? '');
        $status = (string) ($equipment->equipment_inventory_status ?? '');
        $blocked = EquipmentConditions::blocksBorrowing($condition)
            || $status === EquipmentInventoryStatuses::UNDER_MAINTENANCE;

        if (! $blocked) {
            return;
        }

        $name = trim((string) ($equipment->equipment_name ?? ''));
        $label = $name !== '' ? $name : 'This equipment';
        $reason = match (true) {
            $condition === EquipmentConditions::DAMAGED => 'damaged',
            $condition === EquipmentConditions::DEFECTIVE => 'defective',
            default => 'under maintenance',
        };

        throw new RuntimeException($label.' is '.$reason.' and cannot be borrowed.');
    }

    /**
     * Resolve borrower display fields + optional custodian link for a reporter.
     *
     * @return array{name:string,employee_id:string,department:?string,custodian_id:?int}
     */
    public function borrowerFromReporter(object $reporter): array
    {
        $employeeId = (string) ($reporter->reporter_employee_id ?? '');
        $name = (string) ($reporter->reporter_full_name ?? '');
        $department = null;
        $custodianId = null;

        if (Schema::hasTable('custodians_table') && ($employeeId !== '' || ! empty($reporter->reporter_id))) {
            $custodianQuery = DB::table('custodians_table as c')
                ->where(function ($q) use ($employeeId, $reporter) {
                    if ($employeeId !== '') {
                        $q->whereRaw('UPPER(TRIM(c.custodian_employee_id)) = ?', [strtoupper(trim($employeeId))]);
                    }
                    if (! empty($reporter->reporter_id) && Schema::hasColumn('custodians_table', 'custodian_reporter_id')) {
                        $q->orWhere('c.custodian_reporter_id', (int) $reporter->reporter_id);
                    }
                })
                ->where(function ($q) {
                    $q->whereNull('c.custodian_status')
                        ->orWhere('c.custodian_status', '!=', 'Inactive');
                });

            $select = ['c.custodian_id', 'c.custodian_full_name'];
            if (Schema::hasColumn('custodians_table', 'custodian_organization')) {
                $select[] = 'c.custodian_organization';
            }

            if (Schema::hasTable('departments_table') && Schema::hasColumn('custodians_table', 'custodian_department_id')) {
                $custodianQuery->leftJoin(
                    'departments_table as d',
                    'd.department_id',
                    '=',
                    'c.custodian_department_id'
                );
                $select[] = 'd.department_name';
            }

            $custodian = $custodianQuery
                ->addSelect($select)
                ->orderBy('c.custodian_id')
                ->first();

            if ($custodian) {
                $custodianId = (int) $custodian->custodian_id;
                if (filled($custodian->custodian_full_name ?? null)) {
                    $name = (string) $custodian->custodian_full_name;
                }
                $department = filled($custodian->department_name ?? null)
                    ? (string) $custodian->department_name
                    : (filled($custodian->custodian_organization ?? null)
                        ? (string) $custodian->custodian_organization
                        : null);
            }
        }

        return [
            'name' => $name,
            'employee_id' => $employeeId,
            'department' => $department,
            'custodian_id' => $custodianId,
        ];
    }

    /**
     * Create borrowing record lines. Throws ValidationException or RuntimeException.
     *
     * @param  array<string, mixed>  $input
     * @return array{ids:list<int>,slip_no:?string,line_count:int,borrower_name:string,source:string,status:string,borrow_from:?string,expected_return:?string}
     */
    public function create(array $input, ?string $ip = null, ?int $actorUserId = null, string $source = 'admin'): array
    {
        $legacySingle = filled($input['borrowing_equipment_id'] ?? null)
            && empty($input['items']);

        $source = in_array($source, ['admin', 'online'], true) ? $source : 'admin';
        // Online landing submits wait for Property approval; desk create is immediate.
        $initialStatus = $source === 'online' ? 'Pending' : 'Borrowed';

        // Normalize date-only values (YYYY-MM-DD) before validation/storage.
        foreach (['borrowing_date', 'borrowing_expected_return_date'] as $scheduleKey) {
            if (! empty($input[$scheduleKey]) && is_string($input[$scheduleKey])) {
                $input[$scheduleKey] = self::normalizeScheduleDate($input[$scheduleKey]);
            }
        }

        $validated = validator($input, [
            'borrowing_borrower_name' => 'required|string|max:255',
            'borrowing_borrower_department' => 'nullable|string|max:255',
            'borrowing_borrower_custodian_id' => 'nullable|integer|min:1',
            'borrowing_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) {
                    try {
                        $borrowDay = Carbon::parse((string) $value)->startOfDay();
                    } catch (\Throwable $e) {
                        $fail('Enter a valid borrow date.');

                        return;
                    }

                    if ($borrowDay->lt(now()->startOfDay())) {
                        $fail('Borrow date cannot be in the past.');
                    }
                },
            ],
            'borrowing_expected_return_date' => [
                'required',
                'date',
                'after_or_equal:borrowing_date',
                function (string $attribute, mixed $value, \Closure $fail) use ($input) {
                    try {
                        $borrowDay = Carbon::parse((string) ($input['borrowing_date'] ?? ''))->startOfDay();
                        $returnDay = Carbon::parse((string) $value)->startOfDay();
                    } catch (\Throwable $e) {
                        $fail('Enter a valid expected return date.');

                        return;
                    }

                    if ($returnDay->lt($borrowDay)) {
                        $fail('Expected return must be on or after the borrow date.');

                        return;
                    }

                    // Soft max window: 30 days keeps campus equipment circulating.
                    if ($returnDay->gt($borrowDay->copy()->addDays(30))) {
                        $fail('Expected return cannot be more than 30 days after the borrow date.');
                    }
                },
            ],
            'borrowing_purpose' => 'required|string|max:2000',
            'borrowing_event_name' => [
                'nullable',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! filled($value)) {
                        return;
                    }
                    if (! EventNames::isActiveName(is_string($value) ? $value : null)) {
                        $fail('Select a valid event name.');
                    }
                },
            ],
            'borrowing_event_type' => [
                'nullable',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! filled($value)) {
                        return;
                    }
                    if (! EventTypes::isActiveName(is_string($value) ? $value : null)) {
                        $fail('Select a valid event type.');
                    }
                },
            ],
            'borrowing_event_location' => ($source === 'online' ? 'required' : 'nullable').'|string|max:150',
            'borrowing_equipment_condition' => 'nullable|string|max:255',
            'borrowing_equipment_id' => $legacySingle ? 'required|integer' : 'nullable|integer',
            'borrowing_quantity' => $legacySingle ? 'required|integer|min:1|max:5000' : 'nullable|integer|min:1|max:5000',
            'items' => $legacySingle ? 'nullable|array' : 'required|array|min:1|max:100',
            'items.*.equipment_id' => 'required_with:items|integer',
            'items.*.quantity' => 'required_with:items|integer|min:1|max:5000',
            'items.*.condition' => 'nullable|string|max:255',
        ], [
            'borrowing_date.required' => 'Enter the borrow date.',
            'borrowing_expected_return_date.required' => 'Enter the expected return date.',
            'borrowing_expected_return_date.after_or_equal' => 'Expected return must be on or after the borrow date.',
            'borrowing_event_name.max' => 'Event name may not be longer than 50 characters.',
            'borrowing_purpose.required' => 'Enter the purpose of this borrow.',
            'borrowing_event_location.required' => 'Enter where the equipment will be used.',
            'borrowing_event_location.max' => 'Location may not be longer than 150 characters.',
        ])->validate();

        // Store calendar days only: borrow at start of day, return due through end of day.
        $validated['borrowing_date'] = Carbon::parse($validated['borrowing_date'])->startOfDay()->format('Y-m-d H:i:s');
        $validated['borrowing_expected_return_date'] = Carbon::parse($validated['borrowing_expected_return_date'])->endOfDay()->format('Y-m-d H:i:s');

        $borrowerCustodianId = (int) ($validated['borrowing_borrower_custodian_id'] ?? 0);
        if (
            $borrowerCustodianId < 1
            && Schema::hasTable('custodians_table')
            && filled($validated['borrowing_borrower_name'] ?? null)
        ) {
            $borrowerCustodianId = (int) DB::table('custodians_table')
                ->where('custodian_full_name', trim((string) $validated['borrowing_borrower_name']))
                ->value('custodian_id');
        }
        if ($borrowerCustodianId > 0 && ! DB::table('custodians_table')->where('custodian_id', $borrowerCustodianId)->exists()) {
            $borrowerCustodianId = 0;
        }

        $lines = $legacySingle
            ? [[
                'equipment_id' => (int) $validated['borrowing_equipment_id'],
                'quantity' => (int) $validated['borrowing_quantity'],
                'condition' => $validated['borrowing_equipment_condition'] ?? null,
            ]]
            : array_values($validated['items']);

        $merged = [];
        foreach ($lines as $line) {
            $id = (int) $line['equipment_id'];
            if (! isset($merged[$id])) {
                $merged[$id] = [
                    'equipment_id' => $id,
                    'quantity' => 0,
                    'condition' => $line['condition'] ?? null,
                ];
            }
            $merged[$id]['quantity'] += (int) $line['quantity'];
            if (! empty($line['condition'])) {
                $merged[$id]['condition'] = $line['condition'];
            }
        }
        $lines = array_values($merged);

        $createdIds = [];
        $names = [];
        $slipNo = null;

        DB::transaction(function () use (
            $validated,
            $lines,
            $borrowerCustodianId,
            $ip,
            $actorUserId,
            $source,
            $initialStatus,
            &$createdIds,
            &$names,
            &$slipNo
        ) {
            $slipNo = Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')
                ? BorrowingSlipNumber::allocate()
                : null;

            $lines = $this->spreadIndividualQuantity($lines);

            if ($borrowerCustodianId > 0 && Custodians::tableReady()) {
                $custodian = Custodians::find($borrowerCustodianId);
                if ($custodian) {
                    $validated['borrowing_borrower_name'] = (string) ($custodian->custodian_full_name
                        ?? $validated['borrowing_borrower_name']);
                    if (blank($validated['borrowing_borrower_department'] ?? null)) {
                        if (filled($custodian->custodian_department ?? null)) {
                            $validated['borrowing_borrower_department'] = (string) $custodian->custodian_department;
                        } elseif (filled($custodian->department_name ?? null)) {
                            $validated['borrowing_borrower_department'] = (string) $custodian->department_name;
                        } elseif (filled($custodian->custodian_organization ?? null)) {
                            $validated['borrowing_borrower_department'] = (string) $custodian->custodian_organization;
                        }
                    }
                }
            }

            foreach ($lines as $line) {
                $equipment = DB::table('equipment_table')
                    ->where('equipment_id', $line['equipment_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $equipment) {
                    throw new RuntimeException('One or more selected equipment items are not available.');
                }

                $this->assertStoredInInventory($equipment);
                $this->assertBorrowableCondition($equipment);

                if (($equipment->equipment_inventory_status ?? '') === 'Disposed') {
                    throw new RuntimeException(($equipment->equipment_name ?? 'Equipment').' is not available.');
                }

                $stock = max(1, (int) ($equipment->equipment_quantity ?? 1));
                $onLoan = (int) DB::table('borrowing_records_table')
                    ->where('borrowing_equipment_id', $equipment->equipment_id)
                    ->whereIn('borrowing_status', self::RESERVED_STATUSES)
                    ->sum('borrowing_quantity');
                $available = max(0, $stock - $onLoan);

                if ((int) $line['quantity'] > $available) {
                    throw new RuntimeException(
                        ($equipment->equipment_name ?? 'Equipment')
                        .' only has '.$available.' available to borrow.'
                    );
                }

                $borrowInsert = [
                    'borrowing_equipment_id' => $equipment->equipment_id,
                    'borrowing_from_room_id' => $equipment->equipment_room_id,
                    'borrowing_borrower_name' => $validated['borrowing_borrower_name'],
                    'borrowing_borrower_department' => $validated['borrowing_borrower_department'] ?? null,
                    'borrowing_quantity' => (int) $line['quantity'],
                    'borrowing_equipment_condition' => $line['condition']
                        ?? ($validated['borrowing_equipment_condition'] ?? null),
                    'borrowing_date' => $validated['borrowing_date'],
                    'borrowing_expected_return_date' => $validated['borrowing_expected_return_date'],
                    'borrowing_purpose' => trim((string) ($validated['borrowing_purpose'] ?? '')),
                    'borrowing_destination_location' => null,
                    'borrowing_authorized_by' => null,
                    'borrowing_remarks' => null,
                    'borrowing_status' => $initialStatus,
                    'borrowing_created_at' => now(),
                ];
                if (Schema::hasColumn('borrowing_records_table', 'borrowing_source')) {
                    $borrowInsert['borrowing_source'] = $source;
                }
                if ($slipNo && Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')) {
                    $borrowInsert['borrowing_transaction_no'] = $slipNo;
                }
                if (
                    $borrowerCustodianId > 0
                    && Schema::hasColumn('borrowing_records_table', 'borrowing_borrower_custodian_id')
                ) {
                    $borrowInsert['borrowing_borrower_custodian_id'] = $borrowerCustodianId;
                }
                if (Schema::hasColumn('borrowing_records_table', 'borrowing_event_name')) {
                    $borrowInsert['borrowing_event_name'] = filled($validated['borrowing_event_name'] ?? null)
                        ? trim((string) $validated['borrowing_event_name'])
                        : null;
                }
                if (Schema::hasColumn('borrowing_records_table', 'borrowing_event_type')) {
                    $borrowInsert['borrowing_event_type'] = filled($validated['borrowing_event_type'] ?? null)
                        ? (string) $validated['borrowing_event_type']
                        : null;
                }
                if (Schema::hasColumn('borrowing_records_table', 'borrowing_event_location')) {
                    $borrowInsert['borrowing_event_location'] = filled($validated['borrowing_event_location'] ?? null)
                        ? trim((string) $validated['borrowing_event_location'])
                        : null;
                }

                $borrowingId = DB::table('borrowing_records_table')->insertGetId($borrowInsert);

                // Inventory status flips only when the borrow is actually out (desk create / accept).
                if ($initialStatus === 'Borrowed') {
                    $remaining = $available - (int) $line['quantity'];
                    $isBulk = ($equipment->equipment_tracking_mode ?? 'Individual') === 'Bulk';
                    if (! $isBulk || $remaining <= 0) {
                        DB::table('equipment_table')
                            ->where('equipment_id', $equipment->equipment_id)
                            ->update(['equipment_inventory_status' => 'Borrowed']);
                    }
                }

                $eventKey = ($initialStatus === 'Pending' ? 'equipment_borrow_pending_' : 'equipment_borrowed_').$borrowingId;

                if (Schema::hasTable('notifications_table')) {
                    DB::table('notifications_table')->insertOrIgnore([
                        'notification_user_id' => null,
                        'notification_target_role' => WorkflowNotifier::ROLE_ADMIN,
                        'notification_title' => $initialStatus === 'Pending'
                            ? 'Online borrow needs approval'
                            : 'Equipment Borrowed',
                        'notification_message' => ($equipment->equipment_name ?? 'Equipment')
                            .' (x'.((int) $line['quantity']).') '
                            .($initialStatus === 'Pending'
                                ? 'requested online by '
                                : 'was borrowed by ')
                            .$validated['borrowing_borrower_name'].'.',
                        'notification_type' => $initialStatus === 'Pending' ? 'equipment_borrow_pending' : 'equipment_borrowed',
                        'notification_category' => 'Equipment',
                        'notification_reference_type' => 'borrowing_record',
                        'notification_reference_id' => $borrowingId,
                        'notification_url' => $initialStatus === 'Pending'
                            ? '/admin/borrowing?status=Pending&source=online'
                            : '/admin/borrowing',
                        'notification_event_key' => $eventKey,
                        'notification_created_at' => now(),
                    ]);
                }

                $createdIds[] = $borrowingId;
                $names[] = ($equipment->equipment_name ?? 'equipment').' × '.$line['quantity'];
            }

            $count = count($createdIds);
            $lastId = (int) end($createdIds);

            if (Schema::hasTable('audit_logs_table')) {
                DB::table('audit_logs_table')->insert([
                    'audit_log_user_id' => $actorUserId ?? Auth::id(),
                    'audit_log_action' => $initialStatus === 'Pending'
                        ? 'Requested equipment borrow'
                        : 'Borrowed equipment',
                    'audit_log_module' => 'Borrowing',
                    'audit_log_table_name' => 'borrowing_records_table',
                    'audit_log_reference_id' => $lastId,
                    'audit_log_description' => $initialStatus === 'Pending'
                        ? ($count > 1
                            ? ('Online borrow request ('.$count.' lines) from '.$validated['borrowing_borrower_name'].': '.implode(', ', $names).'.')
                            : ('Online borrow request: '.$names[0].' by '.$validated['borrowing_borrower_name'].'.'))
                        : ($count > 1
                            ? ('Borrowed '.$count.' item lines for '.$validated['borrowing_borrower_name'].': '.implode(', ', $names).'.')
                            : ('Borrowed '.$names[0].' to '.$validated['borrowing_borrower_name'].'.')),
                    'audit_log_ip_address' => $ip,
                    'audit_log_created_at' => now(),
                ]);
            }
        });

        return [
            'ids' => $createdIds,
            'slip_no' => $slipNo,
            'line_count' => count($createdIds),
            'borrower_name' => (string) ($validated['borrowing_borrower_name'] ?? ''),
            'source' => $source,
            'status' => $initialStatus,
            'borrow_from' => $validated['borrowing_date'] ?? null,
            'expected_return' => $validated['borrowing_expected_return_date'] ?? null,
        ];
    }

    /**
     * Accept a pending online slip (all lines) → Borrowed + inventory update.
     *
     * @return array{slip_no:string,line_count:int,borrower_name:string}
     */
    public function acceptPendingSlip(string $slipNo, ?int $actorUserId = null, ?string $ip = null): array
    {
        $slipNo = trim($slipNo);
        if ($slipNo === '' || ! Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')) {
            throw new RuntimeException('Missing borrow slip number.');
        }

        $result = [
            'slip_no' => $slipNo,
            'line_count' => 0,
            'borrower_name' => '',
        ];

        DB::transaction(function () use ($slipNo, $actorUserId, $ip, &$result) {
            $lines = DB::table('borrowing_records_table')
                ->where('borrowing_transaction_no', $slipNo)
                ->where('borrowing_status', 'Pending')
                ->orderBy('borrowing_record_id')
                ->lockForUpdate()
                ->get();

            if ($lines->isEmpty()) {
                throw new RuntimeException('No pending borrow request found for that reference.');
            }

            foreach ($lines as $row) {
                $equipment = DB::table('equipment_table')
                    ->where('equipment_id', $row->borrowing_equipment_id)
                    ->lockForUpdate()
                    ->first();

                if (! $equipment) {
                    throw new RuntimeException('One or more requested items are no longer available.');
                }

                $qty = max(1, (int) ($row->borrowing_quantity ?? 1));
                $isBulk = (string) ($equipment->equipment_tracking_mode ?? 'Individual') === 'Bulk';
                $targetEquipment = $equipment;
                $available = 0;

                if ($isBulk) {
                    $this->assertStoredInInventory($equipment);
                    $this->assertBorrowableCondition($equipment);
                    if (($equipment->equipment_inventory_status ?? '') !== EquipmentInventoryStatuses::ACTIVE) {
                        throw new RuntimeException(
                            ($equipment->equipment_name ?? 'Equipment')
                            .' is no longer available to approve. Deny this request or wait for a return.'
                        );
                    }

                    $stock = max(1, (int) ($equipment->equipment_quantity ?? 1));
                    $onLoan = (int) DB::table('borrowing_records_table')
                        ->where('borrowing_equipment_id', $equipment->equipment_id)
                        ->whereIn('borrowing_status', self::RESERVED_STATUSES)
                        ->sum('borrowing_quantity');
                    $available = max(0, $stock - $onLoan);

                    if ($qty > $available) {
                        throw new RuntimeException(
                            ($equipment->equipment_name ?? 'Equipment')
                            .' only has '.$available.' available now ('.$qty.' requested). Another borrow was already approved or issued.'
                        );
                    }
                } else {
                    // For individual units, check if the originally linked unit is still free;
                    // if another approved request took it, transparently allocate another free unit in the same storage pool.
                    $originRoomId = (int) ($row->borrowing_from_room_id ?: ($equipment->equipment_room_id ?? 0));
                    $pool = $this->lockedIndividualPool($equipment, [], $originRoomId);

                    $chosen = null;
                    foreach ($pool as $unit) {
                        if ((int) $unit['id'] === (int) $equipment->equipment_id && (int) $unit['available'] >= $qty) {
                            $chosen = $unit;
                            break;
                        }
                    }
                    if (! $chosen) {
                        foreach ($pool as $unit) {
                            if ((int) $unit['available'] >= $qty) {
                                $chosen = $unit;
                                break;
                            }
                        }
                    }

                    if (! $chosen) {
                        $label = trim((string) ($equipment->equipment_name ?? '')) ?: 'Equipment';
                        throw new RuntimeException(
                            $label.' is no longer available — all units are already borrowed or unavailable. Deny this request or wait for a return.'
                        );
                    }

                    $available = (int) $chosen['available'];
                    if ((int) $chosen['id'] !== (int) $equipment->equipment_id) {
                        $targetEquipment = DB::table('equipment_table')
                            ->where('equipment_id', (int) $chosen['id'])
                            ->lockForUpdate()
                            ->first();
                        if (! $targetEquipment) {
                            throw new RuntimeException('Selected replacement unit is no longer available.');
                        }
                    }
                }

                $targetId = (int) $targetEquipment->equipment_id;

                $patch = [
                    'borrowing_status' => 'Borrowed',
                    'borrowing_equipment_id' => $targetId,
                    'borrowing_from_room_id' => $targetEquipment->equipment_room_id,
                ];
                if (Schema::hasColumn('borrowing_records_table', 'borrowing_decided_at')) {
                    $patch['borrowing_decided_at'] = now();
                }
                if (Schema::hasColumn('borrowing_records_table', 'borrowing_decided_by')) {
                    $patch['borrowing_decided_by'] = $actorUserId;
                }
                if (Schema::hasColumn('borrowing_records_table', 'borrowing_rejection_reason')) {
                    $patch['borrowing_rejection_reason'] = null;
                }

                DB::table('borrowing_records_table')
                    ->where('borrowing_record_id', $row->borrowing_record_id)
                    ->update($patch);

                $remaining = $available - $qty;
                if (! $isBulk || $remaining <= 0) {
                    DB::table('equipment_table')
                        ->where('equipment_id', $targetId)
                        ->update(['equipment_inventory_status' => 'Borrowed']);
                }
            }

            $result['line_count'] = $lines->count();
            $result['borrower_name'] = (string) ($lines->first()->borrowing_borrower_name ?? '');

            if (Schema::hasTable('audit_logs_table')) {
                DB::table('audit_logs_table')->insert([
                    'audit_log_user_id' => $actorUserId ?? Auth::id(),
                    'audit_log_action' => 'Accepted online borrow',
                    'audit_log_module' => 'Borrowing',
                    'audit_log_table_name' => 'borrowing_records_table',
                    'audit_log_reference_id' => (int) $lines->first()->borrowing_record_id,
                    'audit_log_description' => 'Accepted online borrow slip '.$slipNo
                        .' for '.$result['borrower_name'].' ('.$result['line_count'].' line'
                        .($result['line_count'] === 1 ? '' : 's').').',
                    'audit_log_ip_address' => $ip,
                    'audit_log_created_at' => now(),
                ]);
            }
        });

        BorrowingDecisionMail::sendDecision($slipNo, 'accepted');

        return $result;
    }

    /**
     * Deny a pending online slip (all lines).
     *
     * @return array{slip_no:string,line_count:int,borrower_name:string}
     */
    public function denyPendingSlip(string $slipNo, ?string $reason = null, ?int $actorUserId = null, ?string $ip = null): array
    {
        $slipNo = trim($slipNo);
        if ($slipNo === '' || ! Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')) {
            throw new RuntimeException('Missing borrow slip number.');
        }

        $reason = trim((string) $reason);
        if (strlen($reason) > 500) {
            throw new RuntimeException('Denial reason may not be longer than 500 characters.');
        }

        $result = [
            'slip_no' => $slipNo,
            'line_count' => 0,
            'borrower_name' => '',
        ];

        DB::transaction(function () use ($slipNo, $reason, $actorUserId, $ip, &$result) {
            $lines = DB::table('borrowing_records_table')
                ->where('borrowing_transaction_no', $slipNo)
                ->where('borrowing_status', 'Pending')
                ->lockForUpdate()
                ->get();

            if ($lines->isEmpty()) {
                throw new RuntimeException('No pending borrow request found for that slip.');
            }

            $ids = $lines->pluck('borrowing_record_id')->all();
            $patch = ['borrowing_status' => 'Denied'];
            if (Schema::hasColumn('borrowing_records_table', 'borrowing_rejection_reason')) {
                $patch['borrowing_rejection_reason'] = $reason !== '' ? $reason : null;
            }
            if (Schema::hasColumn('borrowing_records_table', 'borrowing_decided_at')) {
                $patch['borrowing_decided_at'] = now();
            }
            if (Schema::hasColumn('borrowing_records_table', 'borrowing_decided_by')) {
                $patch['borrowing_decided_by'] = $actorUserId;
            }

            DB::table('borrowing_records_table')
                ->whereIn('borrowing_record_id', $ids)
                ->update($patch);

            $result['line_count'] = count($ids);
            $result['borrower_name'] = (string) ($lines->first()->borrowing_borrower_name ?? '');

            if (Schema::hasTable('audit_logs_table')) {
                DB::table('audit_logs_table')->insert([
                    'audit_log_user_id' => $actorUserId ?? Auth::id(),
                    'audit_log_action' => 'Denied online borrow',
                    'audit_log_module' => 'Borrowing',
                    'audit_log_table_name' => 'borrowing_records_table',
                    'audit_log_reference_id' => (int) $lines->first()->borrowing_record_id,
                    'audit_log_description' => 'Denied online borrow slip '.$slipNo
                        .' for '.$result['borrower_name']
                        .($reason !== '' ? (': '.$reason) : '.'),
                    'audit_log_ip_address' => $ip,
                    'audit_log_created_at' => now(),
                ]);
            }
        });

        BorrowingDecisionMail::sendDecision($slipNo, 'denied', $reason !== '' ? $reason : null);

        return $result;
    }

    /**
     * Equipment this reporter still has, split into overdue and not-yet-due.
     *
     * @return array{
     *     overdue:int,
     *     open:int,
     *     overdue_items:list<array{name:string,qty:int,days:?int,days_label:string}>,
     *     borrowed_items:list<array{name:string,qty:int,days:?int,days_label:string}>
     * }
     */
    public function cautionForReporter(object $reporter): array
    {
        $empty = [
            'overdue' => 0,
            'open' => 0,
            'overdue_items' => [],
            'borrowed_items' => [],
        ];
        if (! Schema::hasTable('borrowing_records_table')) {
            return $empty;
        }

        $borrower = $this->borrowerFromReporter($reporter);
        $name = trim((string) ($borrower['name'] ?? ''));
        $custodianId = (int) ($borrower['custodian_id'] ?? 0);
        if ($name === '' && $custodianId < 1) {
            return $empty;
        }

        $rows = DB::table('borrowing_records_table')
            ->leftJoin(
                'equipment_table',
                'borrowing_records_table.borrowing_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->whereIn('borrowing_records_table.borrowing_status', ['Borrowed', 'Overdue'])
            ->where(function ($q) use ($name, $custodianId) {
                if ($custodianId > 0 && Schema::hasColumn('borrowing_records_table', 'borrowing_borrower_custodian_id')) {
                    $q->where('borrowing_records_table.borrowing_borrower_custodian_id', $custodianId);
                }
                if ($name !== '') {
                    $method = ($custodianId > 0 && Schema::hasColumn('borrowing_records_table', 'borrowing_borrower_custodian_id'))
                        ? 'orWhereRaw'
                        : 'whereRaw';
                    $q->{$method}(
                        'LOWER(TRIM(borrowing_records_table.borrowing_borrower_name)) = ?',
                        [mb_strtolower($name)]
                    );
                }
            })
            ->orderBy('borrowing_records_table.borrowing_expected_return_date')
            ->orderBy('equipment_table.equipment_name')
            ->get([
                'borrowing_records_table.borrowing_status',
                'borrowing_records_table.borrowing_quantity',
                'borrowing_records_table.borrowing_date',
                'borrowing_records_table.borrowing_expected_return_date',
                'equipment_table.equipment_name',
            ]);

        $overdueItems = [];
        $borrowedItems = [];

        foreach ($rows as $row) {
            $itemName = trim((string) ($row->equipment_name ?? ''));
            $label = $itemName !== '' ? $itemName : 'Equipment';
            $qty = max(1, (int) ($row->borrowing_quantity ?? 1));
            $isOverdue = (string) ($row->borrowing_status ?? '') === 'Overdue';

            if ($isOverdue) {
                $days = $this->daysPast($row->borrowing_expected_return_date ?? null);
                $overdueItems[] = [
                    'name' => $label,
                    'qty' => $qty,
                    'days' => $days,
                    'days_label' => $days === null
                        ? 'Overdue'
                        : ($days === 1 ? '1 day overdue' : $days.' days overdue'),
                ];
                continue;
            }

            $daysOut = $this->daysSince($row->borrowing_date ?? null);
            $borrowedItems[] = [
                'name' => $label,
                'qty' => $qty,
                'days' => $daysOut,
                'days_label' => $daysOut === null
                    ? 'Still borrowed'
                    : ($daysOut === 0
                        ? 'Borrowed today'
                        : ($daysOut === 1 ? '1 day borrowed' : $daysOut.' days borrowed')),
            ];
        }

        return [
            'overdue' => count($overdueItems),
            'open' => count($overdueItems) + count($borrowedItems),
            'overdue_items' => $overdueItems,
            'borrowed_items' => $borrowedItems,
        ];
    }

    private function daysPast(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($date->greaterThanOrEqualTo(today())) {
            return 1;
        }

        return max(1, (int) abs($date->diffInDays(today())));
    }

    private function daysSince(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($date->greaterThan(today())) {
            return 0;
        }

        return max(0, (int) abs($date->diffInDays(today())));
    }

    /**
     * Online borrow slips belonging to a PIN-verified reporter (grouped by slip).
     *
     * @return list<array{
     *     slip_no:string,
     *     status:string,
     *     status_label:string,
     *     borrow_from:?string,
     *     expected_return:?string,
     *     decided_at:?string,
     *     denial_reason:?string,
     *     item_count:int,
     *     items:list<array{name:string,qty:int}>
     * }>
     */
    public function onlineSlipsForReporter(object $reporter): array
    {
        if (! Schema::hasTable('borrowing_records_table')
            || ! Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')) {
            return [];
        }

        $borrower = $this->borrowerFromReporter($reporter);
        $name = trim((string) ($borrower['name'] ?? ''));
        $custodianId = (int) ($borrower['custodian_id'] ?? 0);

        if ($name === '' && $custodianId < 1) {
            return [];
        }

        $query = DB::table('borrowing_records_table')
            ->leftJoin('equipment_table', 'borrowing_records_table.borrowing_equipment_id', '=', 'equipment_table.equipment_id')
            ->when(
                Schema::hasColumn('borrowing_records_table', 'borrowing_source'),
                fn ($q) => $q->where('borrowing_records_table.borrowing_source', 'online')
            )
            ->where(function ($q) use ($name, $custodianId) {
                if ($custodianId > 0 && Schema::hasColumn('borrowing_records_table', 'borrowing_borrower_custodian_id')) {
                    $q->where('borrowing_records_table.borrowing_borrower_custodian_id', $custodianId);
                }
                if ($name !== '') {
                    $method = ($custodianId > 0 && Schema::hasColumn('borrowing_records_table', 'borrowing_borrower_custodian_id'))
                        ? 'orWhereRaw'
                        : 'whereRaw';
                    $q->{$method}('LOWER(TRIM(borrowing_records_table.borrowing_borrower_name)) = ?', [mb_strtolower($name)]);
                }
            })
            ->orderByDesc('borrowing_records_table.borrowing_record_id');

        $select = [
            'borrowing_records_table.borrowing_transaction_no',
            'borrowing_records_table.borrowing_status',
            'borrowing_records_table.borrowing_date',
            'borrowing_records_table.borrowing_expected_return_date',
            'borrowing_records_table.borrowing_quantity',
            'equipment_table.equipment_name',
        ];
        if (Schema::hasColumn('borrowing_records_table', 'borrowing_rejection_reason')) {
            $select[] = 'borrowing_records_table.borrowing_rejection_reason';
        }
        if (Schema::hasColumn('borrowing_records_table', 'borrowing_decided_at')) {
            $select[] = 'borrowing_records_table.borrowing_decided_at';
        }

        $lines = $query->get($select);
        if ($lines->isEmpty()) {
            return [];
        }

        $grouped = [];
        foreach ($lines as $row) {
            $slip = trim((string) ($row->borrowing_transaction_no ?? ''));
            if ($slip === '') {
                continue;
            }
            if (! isset($grouped[$slip])) {
                $status = (string) ($row->borrowing_status ?? 'Pending');
                $grouped[$slip] = [
                    'slip_no' => $slip,
                    'status' => $status,
                    'status_label' => self::teacherStatusLabel($status),
                    'borrow_from' => self::formatPublicDateTime($row->borrowing_date ?? null),
                    'expected_return' => self::formatPublicDateTime($row->borrowing_expected_return_date ?? null),
                    'decided_at' => self::formatPublicDateTime($row->borrowing_decided_at ?? null),
                    'denial_reason' => $status === 'Denied'
                        ? (trim((string) ($row->borrowing_rejection_reason ?? '')) ?: null)
                        : null,
                    'item_count' => 0,
                    'items' => [],
                ];
            }
            $grouped[$slip]['items'][] = [
                'name' => (string) ($row->equipment_name ?: 'Equipment'),
                'qty' => (int) ($row->borrowing_quantity ?? 1),
            ];
            $grouped[$slip]['item_count'] = count($grouped[$slip]['items']);
        }

        return array_values($grouped);
    }

    public static function teacherStatusLabel(string $status): string
    {
        return match ($status) {
            'Pending' => 'Waiting for approval',
            'Borrowed' => 'Approved — ready to claim / out',
            'Overdue' => 'Overdue — return ASAP',
            'Denied' => 'Denied',
            'Returned' => 'Returned',
            default => $status !== '' ? $status : 'Unknown',
        };
    }

    public static function formatPublicDateTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('M j, Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    /**
     * Accept date or leftover datetime input; keep calendar day only.
     */
    public static function normalizeScheduleDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return $value;
        }
    }

    /** @deprecated Use normalizeScheduleDate() */
    public static function normalizeScheduleDateTime(?string $value): ?string
    {
        return self::normalizeScheduleDate($value);
    }
}
