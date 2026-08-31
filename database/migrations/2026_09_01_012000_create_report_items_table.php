<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reports_table')) {
            return;
        }

        if (! Schema::hasTable('report_items_table')) {
            Schema::create('report_items_table', function (Blueprint $table) {
                $table->bigIncrements('report_item_id');
                $table->unsignedBigInteger('report_id');
                $table->unsignedBigInteger('report_item_equipment_id')->nullable();
                $table->string('report_item_unlisted_equipment_name', 255)->nullable();
                $table->string('report_item_suggested_issue', 255)->nullable();
                $table->text('report_item_problem_description')->nullable();
                $table->text('report_item_uploaded_image')->nullable();
                $table->enum('report_item_status', [
                    'Pending',
                    'Processing',
                    'Resolved',
                    'For Replacement',
                    'Rejected',
                ])->default('Pending');
                $table->text('report_item_resolution_notes')->nullable();
                $table->text('report_item_resolution_image')->nullable();
                $table->text('report_item_replacement_notes')->nullable();
                $table->text('report_item_replacement_image')->nullable();
                $table->text('report_item_rejection_notes')->nullable();
                $table->dateTime('report_item_created_at')->nullable();
                $table->dateTime('report_item_updated_at')->nullable();

                $table->index('report_id');
                $table->index('report_item_equipment_id');
                $table->index('report_item_status');
            });
        }

        if (! Schema::hasTable('report_items_table')) {
            return;
        }

        $existingReportIds = DB::table('report_items_table')
            ->distinct()
            ->pluck('report_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $reports = DB::table('reports_table')
            ->orderBy('report_id')
            ->get([
                'report_id',
                'report_equipment_id',
                'report_unlisted_equipment_name',
                'report_suggested_issue',
                'report_problem_description',
                'report_uploaded_image',
                'report_current_status',
                'report_resolution_notes',
                'report_resolution_image',
                'report_replacement_notes',
                'report_replacement_image',
                'report_rejection_notes',
                'report_submitted_at',
                'report_updated_at',
            ]);

        $now = now();
        $rows = [];

        foreach ($reports as $report) {
            $reportId = (int) $report->report_id;

            if (in_array($reportId, $existingReportIds, true)) {
                continue;
            }

            $hasEquipment = ! empty($report->report_equipment_id);
            $hasManual = trim((string) ($report->report_unlisted_equipment_name ?? '')) !== '';

            if (! $hasEquipment && ! $hasManual) {
                continue;
            }

            $rows[] = [
                'report_id' => $reportId,
                'report_item_equipment_id' => $hasEquipment ? (int) $report->report_equipment_id : null,
                'report_item_unlisted_equipment_name' => $hasManual
                    ? $report->report_unlisted_equipment_name
                    : null,
                'report_item_suggested_issue' => $report->report_suggested_issue,
                'report_item_problem_description' => $report->report_problem_description,
                'report_item_uploaded_image' => $report->report_uploaded_image,
                'report_item_status' => $report->report_current_status ?: 'Pending',
                'report_item_resolution_notes' => $report->report_resolution_notes ?? null,
                'report_item_resolution_image' => $report->report_resolution_image ?? null,
                'report_item_replacement_notes' => $report->report_replacement_notes ?? null,
                'report_item_replacement_image' => $report->report_replacement_image ?? null,
                'report_item_rejection_notes' => $report->report_rejection_notes ?? null,
                'report_item_created_at' => $report->report_submitted_at ?? $now,
                'report_item_updated_at' => $report->report_updated_at ?? $now,
            ];

            if (count($rows) >= 200) {
                DB::table('report_items_table')->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('report_items_table')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('report_items_table');
    }
};
