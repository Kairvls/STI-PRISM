<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $categoryIds = [];

        foreach ([
            'Computer' => 'Desktop / system unit',
            'Monitor' => 'Computer monitor / display',
            'Keyboard' => 'Computer keyboard',
            'Mouse' => 'Computer mouse',
            'Speaker' => 'Computer speakers',
            'Webcam' => 'Computer webcam',
            'Headset' => 'Computer headset',
            'AVR / UPS' => 'Power protection for a computer set',
        ] as $name => $unused) {
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

        $computerId = $categoryIds['Computer'];

        $issueMoves = [
            'Monitor' => ['Broken Monitor', 'No Display', 'Flickering Screen', 'Blurry Display'],
            'Keyboard' => ['Keyboard Not Working', 'Missing Keys', 'Keyboard Sticky'],
            'Mouse' => ['Mouse Defective', 'Mouse Not Detected', 'Scroll Wheel Broken'],
            'Speaker' => ['No Sound', 'Speaker Distorted'],
            'Webcam' => ['Webcam Not Detected', 'No Camera Image'],
            'Headset' => ['Headset No Sound', 'Microphone Not Working'],
            'AVR / UPS' => ['No Backup Power', 'AVR Not Turning On'],
        ];

        foreach ($issueMoves as $categoryName => $issueNames) {
            $categoryId = $categoryIds[$categoryName];

            foreach ($issueNames as $issueName) {
                $existing = DB::table('issue_templates_table')
                    ->where('issue_template_name', $issueName)
                    ->first();

                if ($existing) {
                    DB::table('issue_templates_table')
                        ->where('issue_template_id', $existing->issue_template_id)
                        ->update(['issue_template_category_id' => $categoryId]);
                    continue;
                }

                DB::table('issue_templates_table')->insert([
                    'issue_template_category_id' => $categoryId,
                    'issue_template_name' => $issueName,
                    'issue_template_created_at' => $now,
                ]);
            }
        }

        $computerIssues = [
            'No Power',
            'Slow Performance',
            'Cannot Login',
            'Network Connection Lost',
            'System Unit Not Booting',
            'Overheating',
        ];

        foreach ($computerIssues as $issueName) {
            $exists = DB::table('issue_templates_table')
                ->where('issue_template_category_id', $computerId)
                ->where('issue_template_name', $issueName)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('issue_templates_table')->insert([
                'issue_template_category_id' => $computerId,
                'issue_template_name' => $issueName,
                'issue_template_created_at' => $now,
            ]);
        }

        $equipmentMoves = [
            'Monitor' => ['monitor'],
            'Keyboard' => ['keyboard'],
            'Mouse' => ['mouse'],
            'Speaker' => ['speaker'],
            'Webcam' => ['webcam'],
            'Headset' => ['headset', 'headphone'],
            'AVR / UPS' => ['avr', 'ups'],
        ];

        foreach ($equipmentMoves as $categoryName => $needles) {
            DB::table('equipment_table')
                ->where(function ($query) use ($needles) {
                    foreach ($needles as $needle) {
                        $query->orWhere('equipment_name', 'LIKE', '%'.$needle.'%');
                    }
                })
                ->update([
                    'equipment_category_id' => $categoryIds[$categoryName],
                ]);
        }
    }

    public function down(): void
    {
        $computer = DB::table('equipment_categories_table')
            ->where('equipment_category_name', 'Computer')
            ->first();

        if (!$computer) {
            return;
        }

        $splitNames = ['Monitor', 'Keyboard', 'Mouse', 'Speaker', 'Webcam', 'Headset', 'AVR / UPS'];

        $splitIds = DB::table('equipment_categories_table')
            ->whereIn('equipment_category_name', $splitNames)
            ->pluck('equipment_category_id');

        if ($splitIds->isNotEmpty()) {
            DB::table('equipment_table')
                ->whereIn('equipment_category_id', $splitIds)
                ->update(['equipment_category_id' => $computer->equipment_category_id]);

            DB::table('issue_templates_table')
                ->whereIn('issue_template_category_id', $splitIds)
                ->update(['issue_template_category_id' => $computer->equipment_category_id]);
        }
    }
};
