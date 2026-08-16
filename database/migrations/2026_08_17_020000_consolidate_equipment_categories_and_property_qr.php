<?php

use App\Support\EquipmentQrCodes;
use App\Support\SuggestedIssues;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $categoryIds = [];

        foreach (SuggestedIssues::surveyCategories() as $name) {
            $existing = DB::table('equipment_categories_table')
                ->where('equipment_category_name', $name)
                ->first();

            if ($existing) {
                $categoryIds[$name] = (int) $existing->equipment_category_id;
                continue;
            }

            $categoryIds[$name] = (int) DB::table('equipment_categories_table')->insertGetId([
                'equipment_category_name' => $name,
                'equipment_category_created_at' => $now,
            ]);
        }

        $computerId = $categoryIds['Computer Equipment'];
        $aveId = $categoryIds['Audio Visual Equipment'];
        $furnitureId = $categoryIds['Furniture and Fixtures'];

        $mergeInto = [
            'Computer Equipment' => [
                'Computer',
                'Computer Set',
                'Computer Equipment',
                'Monitor',
                'Keyboard',
                'Mouse',
                'Speaker',
                'Webcam',
                'Headset',
                'AVR / UPS',
                'Network Equipment',
            ],
            'Audio Visual Equipment' => [
                'Audio Visual Equipment',
                'Ventilation Equipment',
                'Air Conditioning Equipment',
                'Air Conditioner',
                'Display Equipment',
                'TV',
                'Lighting Equipment',
                'Printing Equipment',
                'Printer',
            ],
            'Furniture and Fixtures' => [
                'Furniture and Fixtures',
            ],
        ];

        foreach ($mergeInto as $targetName => $sourceNames) {
            $targetId = $categoryIds[$targetName];
            $sourceIds = DB::table('equipment_categories_table')
                ->whereIn('equipment_category_name', $sourceNames)
                ->pluck('equipment_category_id');

            if ($sourceIds->isEmpty()) {
                continue;
            }

            DB::table('equipment_table')
                ->whereIn('equipment_category_id', $sourceIds)
                ->update(['equipment_category_id' => $targetId]);

            DB::table('issue_templates_table')
                ->whereIn('issue_template_category_id', $sourceIds)
                ->update(['issue_template_category_id' => $targetId]);
        }

        $equipmentMap = [
            $furnitureId => [
                'office chair', 'monoblock', 'stool', 'arm desk', 'stall chair', 'chair',
                'white board', 'whiteboard', 'office table', 'classroom table', 'classrom table',
                'laboratory table', 'long table', 'table', 'curtain', 'door knob', 'doorknob',
            ],
            $computerId => [
                'keyboard', 'mouse', 'system unit', 'desktop', 'headset', 'headphone',
                'avp', 'webcam', 'ups', 'avr', 'ethernet', 'lan cable', 'network cable',
                'internet cable', 'monitor',
            ],
            $aveId => [
                'projector', 'flat screen', 'television', 'air conditioner', 'aircon',
                'split type', 'window air', 'floor standing', 'ceiling fan', 'wall fan',
                'electric fan', 'fluorescent', 'flourescent', 'led light', 'led bulb',
                'cfl', 'compact fluorescent', 'printer',
            ],
        ];

        foreach ($equipmentMap as $categoryId => $needles) {
            DB::table('equipment_table')
                ->where(function ($query) use ($needles) {
                    foreach ($needles as $needle) {
                        $query->orWhere('equipment_name', 'LIKE', '%'.$needle.'%');
                    }
                })
                ->update(['equipment_category_id' => $categoryId]);
        }

        DB::table('equipment_table')
            ->where('equipment_name', 'LIKE', '%tv%')
            ->where('equipment_name', 'NOT LIKE', '%active%')
            ->update(['equipment_category_id' => $aveId]);

        DB::table('equipment_table')
            ->where(function ($query) {
                $query
                    ->where('equipment_name', 'LIKE', '%fan%')
                    ->where('equipment_name', 'NOT LIKE', '%ceiling%')
                    ->where('equipment_name', 'NOT LIKE', '%wall%');
            })
            ->update(['equipment_category_id' => $aveId]);

        foreach (SuggestedIssues::defaultIssues() as $categoryName => $components) {
            $categoryId = $categoryIds[$categoryName];

            foreach ($components as $component => $issues) {
                foreach ($issues as $issueName) {
                    $exists = DB::table('issue_templates_table')
                        ->where('issue_template_category_id', $categoryId)
                        ->where('issue_template_name', $issueName)
                        ->where('issue_template_component', $component)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('issue_templates_table')->insert([
                        'issue_template_category_id' => $categoryId,
                        'issue_template_component' => $component,
                        'issue_template_name' => $issueName,
                        'issue_template_created_at' => $now,
                    ]);
                }
            }
        }

        $keepIds = array_values($categoryIds);

        $legacyNames = [
            'Computer', 'Computer Set', 'Monitor', 'Keyboard', 'Mouse', 'Speaker',
            'Webcam', 'Headset', 'AVR / UPS', 'Network Equipment',
            'Ventilation Equipment', 'Air Conditioning Equipment', 'Air Conditioner',
            'Display Equipment', 'TV', 'Lighting Equipment', 'Printing Equipment', 'Printer',
        ];

        DB::table('equipment_categories_table')
            ->whereIn('equipment_category_name', $legacyNames)
            ->whereNotIn('equipment_category_id', $keepIds)
            ->delete();

        $eligible = DB::table('equipment_table')
            ->whereIn('equipment_category_id', [$computerId, $aveId])
            ->where(function ($query) {
                $query
                    ->whereNull('equipment_qr_code')
                    ->orWhere('equipment_qr_code', '');
            })
            ->orderBy('equipment_id')
            ->pluck('equipment_id');

        foreach ($eligible as $equipmentId) {
            EquipmentQrCodes::assignIfEligible((int) $equipmentId);
        }
    }

    public function down(): void
    {
        // Category consolidation is kept.
    }
};
