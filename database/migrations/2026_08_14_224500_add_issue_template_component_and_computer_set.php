<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('issue_templates_table', 'issue_template_component')) {
            Schema::table('issue_templates_table', function (Blueprint $table) {
                $table->string('issue_template_component', 64)
                    ->nullable()
                    ->after('issue_template_category_id');
            });
        }

        $computer = DB::table('equipment_categories_table')
            ->whereIn('equipment_category_name', ['Computer', 'Computer Set'])
            ->orderBy('equipment_category_id')
            ->first();

        if (!$computer) {
            $computerId = DB::table('equipment_categories_table')->insertGetId([
                'equipment_category_name' => 'Computer Set',
                'equipment_category_created_at' => now(),
            ]);
        } else {
            $computerId = (int) $computer->equipment_category_id;

            DB::table('equipment_categories_table')
                ->where('equipment_category_id', $computerId)
                ->update(['equipment_category_name' => 'Computer Set']);
        }

        $splitNames = ['Monitor', 'Keyboard', 'Mouse', 'Speaker', 'Webcam', 'Headset', 'AVR / UPS'];

        $splitCategories = DB::table('equipment_categories_table')
            ->whereIn('equipment_category_name', $splitNames)
            ->get();

        foreach ($splitCategories as $category) {
            DB::table('issue_templates_table')
                ->where('issue_template_category_id', $category->equipment_category_id)
                ->update([
                    'issue_template_category_id' => $computerId,
                    'issue_template_component' => $category->equipment_category_name,
                ]);

            DB::table('equipment_table')
                ->where('equipment_category_id', $category->equipment_category_id)
                ->update([
                    'equipment_category_id' => $computerId,
                ]);

            DB::table('equipment_categories_table')
                ->where('equipment_category_id', $category->equipment_category_id)
                ->delete();
        }

        $componentByIssue = [
            'Broken Monitor' => 'Monitor',
            'No Display' => 'Monitor',
            'Flickering Screen' => 'Monitor',
            'Blurry Display' => 'Monitor',
            'Keyboard Not Working' => 'Keyboard',
            'Missing Keys' => 'Keyboard',
            'Keyboard Sticky' => 'Keyboard',
            'Mouse Defective' => 'Mouse',
            'Mouse Not Detected' => 'Mouse',
            'Scroll Wheel Broken' => 'Mouse',
            'No Sound' => 'Speaker',
            'Speaker Distorted' => 'Speaker',
            'Webcam Not Detected' => 'Webcam',
            'No Camera Image' => 'Webcam',
            'Headset No Sound' => 'Headset',
            'Microphone Not Working' => 'Headset',
            'No Backup Power' => 'AVR / UPS',
            'AVR Not Turning On' => 'AVR / UPS',
            'No Power' => 'System Unit',
            'Slow Performance' => 'System Unit',
            'Cannot Login' => 'System Unit',
            'Network Connection Lost' => 'System Unit',
            'System Unit Not Booting' => 'System Unit',
            'Overheating' => 'System Unit',
        ];

        foreach ($componentByIssue as $issueName => $component) {
            DB::table('issue_templates_table')
                ->where('issue_template_category_id', $computerId)
                ->where('issue_template_name', $issueName)
                ->update(['issue_template_component' => $component]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('issue_templates_table', 'issue_template_component')) {
            Schema::table('issue_templates_table', function (Blueprint $table) {
                $table->dropColumn('issue_template_component');
            });
        }
    }
};
