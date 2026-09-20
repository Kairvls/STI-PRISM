<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array{0:string,1:string}> table => [column, index] */
    private array $columns = [
        'requisition_issue_slip_table' => ['ris_assigned_reviewer_id', 'ris_assigned_reviewer_idx'],
        'authority_to_purchase_table' => ['authority_purchase_assigned_reviewer_id', 'atp_assigned_reviewer_idx'],
        'purchase_orders_table' => ['purchase_order_assigned_reviewer_id', 'po_assigned_reviewer_idx'],
        'request_check_table' => ['request_check_assigned_reviewer_id', 'rfc_assigned_reviewer_idx'],
        'receiving_reports_table' => ['receiving_report_assigned_reviewer_id', 'rr_assigned_reviewer_idx'],
        'liquidation_reports_table' => ['liquidation_report_assigned_reviewer_id', 'liq_assigned_reviewer_idx'],
        'procurement_record_packages_table' => ['package_assigned_reviewer_id', 'pkg_assigned_reviewer_idx'],
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => [$column, $index]) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($column, $index) {
                $blueprint->unsignedBigInteger($column)->nullable()->index($index);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $table => [$column, $index]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($column, $index) {
                try {
                    $blueprint->dropIndex($index);
                } catch (\Throwable $e) {
                    // Index may already be missing.
                }
                $blueprint->dropColumn($column);
            });
        }
    }
};
