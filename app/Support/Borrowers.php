<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Borrowing people — separate from campus faculty/staff (custodians used for property).
 * Registered borrowers are External custodians; history also comes from borrowing_records_table.
 */
class Borrowers
{
    /**
     * People selectable in the Borrow Equipment "Registered" picker.
     * External registry only — not the full campus staff directory.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function registeredOptions(): Collection
    {
        if (! Custodians::tableReady() || ! Schema::hasColumn('custodians_table', 'custodian_affiliation')) {
            return collect();
        }

        return Custodians::withDepartment(DB::table('custodians_table as person'))
            ->where('person.custodian_status', Custodians::STATUS_ACTIVE)
            ->where('person.custodian_affiliation', Custodians::AFFILIATION_EXTERNAL)
            ->orderBy('person.custodian_full_name')
            ->get([
                'person.custodian_id',
                'person.custodian_full_name',
                'person.custodian_position',
                'person.custodian_employee_id',
                'person.custodian_organization',
                'person.custodian_affiliation',
                Custodians::departmentNameColumn(),
            ])
            ->map(fn ($person) => [
                'id' => (int) $person->custodian_id,
                'name' => (string) $person->custodian_full_name,
                'department' => (string) ($person->custodian_department ?? ''),
                'organization' => (string) ($person->custodian_organization ?? ''),
                'affiliation' => Custodians::AFFILIATION_EXTERNAL,
                'position' => (string) ($person->custodian_position ?? ''),
                'employee_id' => (string) ($person->custodian_employee_id ?? ''),
                'label' => Custodians::optionLabel($person),
            ])
            ->values();
    }

    /**
     * Directory rows: registered External borrowers + people who already appear in borrow history.
     *
     * @return Collection<int, object>
     */
    public static function directory(?string $q = null, string $filter = 'all'): Collection
    {
        $q = trim((string) $q);
        $filter = in_array($filter, ['all', 'registered', 'history'], true) ? $filter : 'all';

        $registered = collect();
        if (Custodians::tableReady() && Schema::hasColumn('custodians_table', 'custodian_affiliation')) {
            $query = Custodians::withDepartment(DB::table('custodians_table as person'))
                ->where('person.custodian_status', Custodians::STATUS_ACTIVE)
                ->where('person.custodian_affiliation', Custodians::AFFILIATION_EXTERNAL);

            if ($q !== '') {
                $like = '%'.$q.'%';
                $query->where(function ($builder) use ($like) {
                    $builder->where('person.custodian_full_name', 'like', $like)
                        ->orWhere('person.custodian_position', 'like', $like)
                        ->orWhere('person.custodian_email_address', 'like', $like)
                        ->orWhere('person.custodian_contact_number', 'like', $like)
                        ->orWhere('person.custodian_organization', 'like', $like);
                });
            }

            $registered = $query
                ->orderBy('person.custodian_full_name')
                ->get([
                    'person.custodian_id',
                    'person.custodian_full_name',
                    'person.custodian_position',
                    'person.custodian_email_address',
                    'person.custodian_contact_number',
                    'person.custodian_organization',
                    'person.custodian_affiliation',
                    Custodians::departmentNameColumn(),
                ])
                ->map(function ($person) {
                    return (object) [
                        'source' => 'registered',
                        'custodian_id' => (int) $person->custodian_id,
                        'name' => (string) $person->custodian_full_name,
                        'affiliation' => Custodians::AFFILIATION_EXTERNAL,
                        'organization' => (string) ($person->custodian_organization ?: $person->custodian_department ?: ''),
                        'position' => (string) ($person->custodian_position ?? ''),
                        'email' => (string) ($person->custodian_email_address ?? ''),
                        'contact' => (string) ($person->custodian_contact_number ?? ''),
                        'is_registered' => true,
                    ];
                });
        }

        $history = collect();
        if (Schema::hasTable('borrowing_records_table')) {
            $rows = DB::table('borrowing_records_table')
                ->whereNotNull('borrowing_borrower_name')
                ->where('borrowing_borrower_name', '!=', '')
                ->get([
                    'borrowing_borrower_name',
                    'borrowing_borrower_department',
                    'borrowing_status',
                    'borrowing_date',
                    'borrowing_event_name',
                    'borrowing_borrower_custodian_id',
                ]);

            $history = $rows
                ->groupBy(fn ($row) => mb_strtolower(trim((string) $row->borrowing_borrower_name)))
                ->map(function ($group) {
                    $first = $group->first();
                    $name = trim((string) $first->borrowing_borrower_name);
                    $org = $group
                        ->map(fn ($row) => trim((string) ($row->borrowing_borrower_department ?? '')))
                        ->filter()
                        ->sortByDesc(fn ($value) => strlen($value))
                        ->first() ?? '';
                    $linked = $group
                        ->map(fn ($row) => (int) ($row->borrowing_borrower_custodian_id ?? 0))
                        ->filter(fn ($id) => $id > 0)
                        ->first() ?? 0;

                    return (object) [
                        'source' => 'history',
                        'custodian_id' => $linked ?: null,
                        'name' => $name,
                        'affiliation' => 'External',
                        'organization' => $org,
                        'position' => '',
                        'email' => '',
                        'contact' => '',
                        'is_registered' => $linked > 0,
                        'total_borrows' => $group->count(),
                        'open_borrows' => $group->whereIn('borrowing_status', ['Borrowed', 'Overdue'])->count(),
                        'overdue_borrows' => $group->where('borrowing_status', 'Overdue')->count(),
                        'event_borrows' => $group->filter(fn ($row) => filled($row->borrowing_event_name ?? null))->count(),
                        'last_borrow_date' => $group->max('borrowing_date'),
                    ];
                })
                ->values();

            if ($q !== '') {
                $needle = mb_strtolower($q);
                $history = $history->filter(function ($row) use ($needle) {
                    return str_contains(mb_strtolower($row->name), $needle)
                        || str_contains(mb_strtolower($row->organization), $needle);
                })->values();
            }
        }

        // Attach activity onto registered rows; drop history rows already covered by registered names.
        $registeredNames = $registered->mapWithKeys(fn ($row) => [mb_strtolower($row->name) => $row]);

        $historyByName = $history->keyBy(fn ($row) => mb_strtolower($row->name));

        $registered = $registered->map(function ($row) use ($historyByName) {
            $hist = $historyByName->get(mb_strtolower($row->name));
            $row->total_borrows = (int) ($hist->total_borrows ?? 0);
            $row->open_borrows = (int) ($hist->open_borrows ?? 0);
            $row->overdue_borrows = (int) ($hist->overdue_borrows ?? 0);
            $row->event_borrows = (int) ($hist->event_borrows ?? 0);
            $row->last_borrow_date = $hist->last_borrow_date ?? null;
            if ($row->organization === '' && $hist && $hist->organization !== '') {
                $row->organization = $hist->organization;
            }

            return $row;
        });

        $historyOnly = $history->reject(function ($row) use ($registeredNames) {
            return $registeredNames->has(mb_strtolower($row->name));
        })->values();

        $merged = match ($filter) {
            'registered' => $registered->values(),
            'history' => $historyOnly,
            default => $registered->concat($historyOnly)->sortBy(fn ($row) => mb_strtolower($row->name), SORT_NATURAL | SORT_FLAG_CASE)->values(),
        };

        return $merged;
    }

    /**
     * @return array{total:int,registered:int,history:int,with_open:int,with_overdue:int}
     */
    public static function stats(): array
    {
        $all = self::directory(null, 'all');

        return [
            'total' => $all->count(),
            'registered' => $all->where('is_registered', true)->count(),
            'history' => $all->where('is_registered', false)->count(),
            'with_open' => $all->where('open_borrows', '>', 0)->count(),
            'with_overdue' => $all->where('overdue_borrows', '>', 0)->count(),
        ];
    }

    /**
     * Borrowing rows for one person (exact name match, case-insensitive trim).
     *
     * @return Collection<int, object>
     */
    public static function recordsForName(string $name): Collection
    {
        $name = trim($name);
        if ($name === '' || ! Schema::hasTable('borrowing_records_table')) {
            return collect();
        }

        $query = DB::table('borrowing_records_table')
            ->leftJoin(
                'equipment_table',
                'borrowing_records_table.borrowing_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->whereRaw('TRIM(borrowing_borrower_name) = ?', [$name])
            ->orderByDesc('borrowing_records_table.borrowing_date')
            ->orderByDesc('borrowing_records_table.borrowing_record_id');

        $columns = [
            'borrowing_records_table.borrowing_record_id',
            'borrowing_records_table.borrowing_borrower_name',
            'borrowing_records_table.borrowing_borrower_department',
            'borrowing_records_table.borrowing_status',
            'borrowing_records_table.borrowing_date',
            'borrowing_records_table.borrowing_expected_return_date',
            'borrowing_records_table.borrowing_actual_return_date',
            'borrowing_records_table.borrowing_purpose',
            'equipment_table.equipment_name',
            'equipment_table.equipment_asset_tag',
        ];

        if (Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')) {
            $columns[] = 'borrowing_records_table.borrowing_transaction_no';
        }
        if (Schema::hasColumn('borrowing_records_table', 'borrowing_event_name')) {
            $columns[] = 'borrowing_records_table.borrowing_event_name';
            $columns[] = 'borrowing_records_table.borrowing_event_location';
        }

        return $query->get($columns)->map(function ($row) {
            return (object) [
                'id' => (int) $row->borrowing_record_id,
                'slip' => (string) ($row->borrowing_transaction_no ?? ''),
                'equipment' => (string) ($row->equipment_name ?? 'Equipment'),
                'asset_tag' => (string) ($row->equipment_asset_tag ?? ''),
                'status' => (string) ($row->borrowing_status ?? ''),
                'borrow_date' => $row->borrowing_date,
                'expected_return' => $row->borrowing_expected_return_date,
                'actual_return' => $row->borrowing_actual_return_date,
                'purpose' => (string) ($row->borrowing_purpose ?? ''),
                'organization' => (string) ($row->borrowing_borrower_department ?? ''),
                'event_name' => (string) ($row->borrowing_event_name ?? ''),
                'event_location' => (string) ($row->borrowing_event_location ?? ''),
            ];
        })->values();
    }
}
