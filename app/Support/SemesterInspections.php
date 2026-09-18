<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SemesterInspections
{
    public const STATUSES = ['Draft', 'Active', 'In Progress', 'Completed', 'Cancelled'];

    public const SEMESTERS = ['1st Semester', '2nd Semester', 'Summer'];

    public const SCOPE_TYPES = ['campus', 'building', 'floor'];

    public const CONDITIONS = ['OK', 'Malfunctioning', 'Defective', 'Destroyed'];

    public static function tablesReady(): bool
    {
        return Schema::hasTable('semester_inspection_campaigns_table')
            && Schema::hasTable('semester_inspection_items_table');
    }

    public static function equipmentQueryForScope(
        string $scopeType,
        ?int $buildingId = null,
        ?int $floorId = null
    ) {
        $query = DB::table('equipment_table')
            ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'equipment_table.equipment_room_id')
            ->leftJoin('floors_table', 'floors_table.floor_id', '=', 'rooms_table.room_floor_id')
            ->leftJoin('buildings_table', 'buildings_table.building_id', '=', 'floors_table.floor_building_id')
            ->where(function ($q) {
                $q->whereNull('equipment_table.equipment_inventory_status')
                    ->orWhere('equipment_table.equipment_inventory_status', '!=', 'Disposed');
            });

        if (Schema::hasColumn('rooms_table', 'room_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('rooms_table.room_is_archived')
                    ->orWhere('rooms_table.room_is_archived', 0);
            });
        }

        $scopeType = strtolower($scopeType);

        if ($scopeType === 'building' && $buildingId) {
            $query->where('buildings_table.building_id', $buildingId);
        } elseif ($scopeType === 'floor' && $floorId) {
            $query->where('floors_table.floor_id', $floorId);
        }

        return $query;
    }

    /**
     * Map inspection condition → equipment condition + inventory updates.
     *
     * @return array{condition: ?string, inventory: ?string}
     */
    public static function statusMapping(string $condition): array
    {
        return match ($condition) {
            'OK' => [
                'condition' => 'Good',
                'inventory' => null,
            ],
            'Malfunctioning' => [
                'condition' => 'Damaged',
                'inventory' => 'Under Maintenance',
            ],
            'Defective' => [
                'condition' => 'Damaged',
                'inventory' => 'For Replacement',
            ],
            'Destroyed' => [
                'condition' => 'Damaged',
                'inventory' => 'For Replacement',
            ],
            default => [
                'condition' => null,
                'inventory' => null,
            ],
        };
    }

    public static function campaignProgress(int $campaignId): array
    {
        if (! self::tablesReady()) {
            return [
                'total' => 0,
                'inspected' => 0,
                'pending' => 0,
                'defects' => 0,
                'percent' => 0,
            ];
        }

        $total = (int) DB::table('semester_inspection_items_table')
            ->where('item_campaign_id', $campaignId)
            ->count();

        $inspected = (int) DB::table('semester_inspection_items_table')
            ->where('item_campaign_id', $campaignId)
            ->where('item_status', 'Inspected')
            ->count();

        $defects = (int) DB::table('semester_inspection_items_table')
            ->where('item_campaign_id', $campaignId)
            ->whereIn('item_condition', ['Malfunctioning', 'Defective', 'Destroyed'])
            ->count();

        $pending = max(0, $total - $inspected);

        return [
            'total' => $total,
            'inspected' => $inspected,
            'pending' => $pending,
            'defects' => $defects,
            'percent' => $total > 0 ? (int) round(($inspected / $total) * 100) : 0,
        ];
    }

    public static function activeCampaignsDueSoon(int $withinDays = 7, int $limit = 6): Collection
    {
        if (! self::tablesReady()) {
            return collect();
        }

        $horizon = now()->addDays($withinDays)->toDateString();

        return DB::table('semester_inspection_campaigns_table')
            ->whereIn('campaign_status', ['Active', 'In Progress'])
            ->whereNotNull('campaign_due_date')
            ->where(function ($q) use ($horizon) {
                $q->whereDate('campaign_due_date', '<=', $horizon)
                    ->orWhereDate('campaign_due_date', '<', today());
            })
            ->orderBy('campaign_due_date')
            ->limit($limit)
            ->get();
    }

    public static function scopeLabel(object $campaign): string
    {
        $type = strtolower((string) ($campaign->campaign_scope_type ?? 'campus'));

        if ($type === 'building' && ! empty($campaign->building_name)) {
            return 'Building: '.$campaign->building_name;
        }

        if ($type === 'floor') {
            if (isset($campaign->floor_level)) {
                $building = (string) ($campaign->building_name ?? '');
                // Single-school setup often names the only building after the school.
                if ($building !== '' && ! preg_match('/STI College Ormoc/i', $building)) {
                    return $building.' · Floor '.$campaign->floor_level;
                }

                return 'Floor '.$campaign->floor_level;
            }

            return 'One floor';
        }

        return 'Entire campus';
    }
}
