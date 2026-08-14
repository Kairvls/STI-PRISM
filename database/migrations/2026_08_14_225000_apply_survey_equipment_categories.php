<?php

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

        $renames = [
            'Computer' => 'Computer Equipment',
            'Computer Set' => 'Computer Equipment',
            'Air Conditioner' => 'Air Conditioning Equipment',
            'TV' => 'Display Equipment',
            'Printer' => 'Printing Equipment',
        ];

        foreach ($renames as $from => $to) {
            $row = DB::table('equipment_categories_table')
                ->where('equipment_category_name', $from)
                ->first();

            if (!$row) {
                continue;
            }

            DB::table('equipment_table')
                ->where('equipment_category_id', $row->equipment_category_id)
                ->update(['equipment_category_id' => $categoryIds[$to]]);

            DB::table('issue_templates_table')
                ->where('issue_template_category_id', $row->equipment_category_id)
                ->update(['issue_template_category_id' => $categoryIds[$to]]);

            if ((int) $row->equipment_category_id !== $categoryIds[$to]) {
                DB::table('equipment_categories_table')
                    ->where('equipment_category_id', $row->equipment_category_id)
                    ->delete();
            }
        }

        $equipmentMap = [
            'Furniture and Fixtures' => ['office chair', 'monoblock', 'stool', 'arm desk', 'stall chair', 'chair', 'white board', 'whiteboard', 'office table', 'classroom table', 'classrom table', 'laboratory table', 'long table', 'table', 'curtain'],
            'Ventilation Equipment' => ['ceiling fan', 'wall fan', 'electric fan'],
            'Air Conditioning Equipment' => ['floor standing', 'window air', 'split type', 'air conditioner', 'aircon'],
            'Display Equipment' => ['flat screen', 'television'],
            'Computer Equipment' => ['monitor', 'mouse', 'keyboard', 'system unit', 'desktop', 'ups', 'avr'],
            'Network Equipment' => ['ethernet', 'internet cable', 'lan cable', 'network cable'],
            'Lighting Equipment' => ['compact fluorescent', 'fluorescent', 'flourescent', 'led light', 'led bulb', 'cfl', 'bulb'],
            'Printing Equipment' => ['printer'],
        ];

        foreach ($equipmentMap as $categoryName => $needles) {
            DB::table('equipment_table')
                ->where(function ($query) use ($needles) {
                    foreach ($needles as $needle) {
                        $query->orWhere('equipment_name', 'LIKE', '%'.$needle.'%');
                    }
                })
                ->update(['equipment_category_id' => $categoryIds[$categoryName]]);
        }

        DB::table('equipment_table')
            ->where('equipment_name', 'LIKE', '%tv%')
            ->where('equipment_name', 'NOT LIKE', '%active%')
            ->update(['equipment_category_id' => $categoryIds['Display Equipment']]);

        DB::table('equipment_table')
            ->where(function ($query) {
                $query
                    ->where('equipment_name', 'LIKE', '%fan%')
                    ->where('equipment_name', 'NOT LIKE', '%ceiling%')
                    ->where('equipment_name', 'NOT LIKE', '%wall%');
            })
            ->update(['equipment_category_id' => $categoryIds['Ventilation Equipment']]);

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

        $equipment = DB::table('equipment_table')->get(['equipment_id', 'equipment_name', 'equipment_category_id']);

        foreach ($equipment as $item) {
            $component = SuggestedIssues::detectComponent($item->equipment_name);

            if (!$component) {
                continue;
            }

            DB::table('issue_templates_table')
                ->where('issue_template_category_id', $item->equipment_category_id)
                ->where('issue_template_name', 'LIKE', '%'.$component.'%')
                ->where(function ($query) {
                    $query
                        ->whereNull('issue_template_component')
                        ->orWhere('issue_template_component', '');
                })
                ->update(['issue_template_component' => $component]);
        }
    }

    public function down(): void
    {
        // Survey categories are kept; no destructive rollback.
    }
};
