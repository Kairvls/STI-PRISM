<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('semester_inspection_items_table')) {
            Schema::table('semester_inspection_items_table', function (Blueprint $table) {
                if (! Schema::hasColumn('semester_inspection_items_table', 'item_custodian_reporter_id')) {
                    $table->unsignedBigInteger('item_custodian_reporter_id')->nullable()->index('sii_custodian_idx');
                }
                if (! Schema::hasColumn('semester_inspection_items_table', 'item_custodian_verified')) {
                    $table->boolean('item_custodian_verified')->nullable();
                }
            });
        }

        if (Schema::hasTable('property_assignments_table')) {
            Schema::table('property_assignments_table', function (Blueprint $table) {
                if (! Schema::hasColumn('property_assignments_table', 'assignment_verified_at')) {
                    $table->timestamp('assignment_verified_at')->nullable();
                }
                if (! Schema::hasColumn('property_assignments_table', 'assignment_verified_by')) {
                    $table->unsignedBigInteger('assignment_verified_by')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('semester_inspection_items_table')) {
            Schema::table('semester_inspection_items_table', function (Blueprint $table) {
                if (Schema::hasColumn('semester_inspection_items_table', 'item_custodian_reporter_id')) {
                    $table->dropIndex('sii_custodian_idx');
                    $table->dropColumn('item_custodian_reporter_id');
                }
                if (Schema::hasColumn('semester_inspection_items_table', 'item_custodian_verified')) {
                    $table->dropColumn('item_custodian_verified');
                }
            });
        }

        if (Schema::hasTable('property_assignments_table')) {
            Schema::table('property_assignments_table', function (Blueprint $table) {
                foreach (['assignment_verified_at', 'assignment_verified_by'] as $column) {
                    if (Schema::hasColumn('property_assignments_table', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
