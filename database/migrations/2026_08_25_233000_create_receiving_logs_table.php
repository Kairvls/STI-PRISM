<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('receiving_logs_table')) {
            Schema::create('receiving_logs_table', function (Blueprint $table) {
                $table->bigIncrements('receiving_log_id');
                $table->unsignedBigInteger('receiving_report_id')->nullable()->index();
                $table->unsignedBigInteger('receiving_log_atp_id')->nullable()->index();
                $table->string('receiving_log_action', 255)->nullable();
                $table->string('receiving_log_status', 100)->nullable()->index();
                $table->text('receiving_log_remarks')->nullable();
                $table->unsignedBigInteger('receiving_log_officer_id')->nullable()->index();
                $table->dateTime('receiving_log_created_at')->useCurrent();
            });
        } else {
            Schema::table('receiving_logs_table', function (Blueprint $table) {
                if (!Schema::hasColumn('receiving_logs_table', 'receiving_log_status')) {
                    $table->string('receiving_log_status', 100)->nullable()->after('receiving_log_action')->index();
                }
            });
        }

        // Backfill completed / returned reports that never had log rows.
        if (
            Schema::hasTable('receiving_logs_table')
            && Schema::hasTable('receiving_reports_table')
        ) {
            $existing = DB::table('receiving_logs_table')
                ->whereNotNull('receiving_report_id')
                ->pluck('receiving_report_id')
                ->flip();

            $reports = DB::table('receiving_reports_table')
                ->whereIn('receiving_report_status', ['Completed', 'Accepted', 'Returned'])
                ->orderBy('receiving_report_id')
                ->get();

            foreach ($reports as $rr) {
                $id = (int) ($rr->receiving_report_id ?? 0);
                if ($id < 1 || isset($existing[$id])) {
                    continue;
                }

                $status = (string) ($rr->receiving_report_status ?? '');
                $isReturned = strcasecmp($status, 'Returned') === 0;
                $displayStatus = $isReturned ? 'Returned' : 'Delivered';
                $action = $isReturned ? 'Returned for correction' : 'Second count completed';
                $remarks = $isReturned
                    ? (trim((string) ($rr->receiving_report_return_reason ?? '')) ?: 'Returned to Purchaser.')
                    : 'Items delivered. Inventory updated.';

                $createdAt = $rr->receiving_report_second_count_at
                    ?? $rr->receiving_report_updated_at
                    ?? $rr->receiving_report_created_at
                    ?? now();

                DB::table('receiving_logs_table')->insert([
                    'receiving_report_id' => $id,
                    'receiving_log_atp_id' => !empty($rr->receiving_report_atp_id) ? (int) $rr->receiving_report_atp_id : null,
                    'receiving_log_action' => $action,
                    'receiving_log_status' => $displayStatus,
                    'receiving_log_remarks' => $remarks,
                    'receiving_log_officer_id' => !empty($rr->receiving_report_second_count_by_user_id)
                        ? (int) $rr->receiving_report_second_count_by_user_id
                        : null,
                    'receiving_log_created_at' => $createdAt,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep audit history; do not drop the table on rollback.
    }
};
