<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Department / person an Inventory transfer was handed to, on top of the destination room.
 */
class TransferDestinations
{
    public static function ready(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasColumn('equipment_transfer_history_table', 'to_department_id');
    }

    /**
     * Joins the destination department and person onto a query over equipment_transfer_history_table.
     *
     * @return array<int, string> columns to add to the select
     */
    public static function join($query): array
    {
        if (! self::ready()) {
            return [];
        }

        $columns = [];

        if (Departments::tableReady()) {
            $query->leftJoin(
                'departments_table as to_dept',
                'to_dept.department_id',
                '=',
                'equipment_transfer_history_table.to_department_id'
            );
            $columns[] = 'to_dept.department_name as to_department_name';
        }

        if (Custodians::tableReady()) {
            $query->leftJoin(
                'custodians_table as to_person',
                'to_person.custodian_id',
                '=',
                'equipment_transfer_history_table.to_custodian_id'
            );
            $columns[] = 'to_person.custodian_full_name as to_custodian_name';
        }

        return $columns;
    }

    /**
     * "Maria Santos · Registrar" style label, or null for plain room moves.
     */
    public static function label(object $row): ?string
    {
        $parts = array_filter([
            trim((string) ($row->to_custodian_name ?? '')),
            trim((string) ($row->to_department_name ?? '')),
        ]);

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
