<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_verified')) {
                // Set once the Receiving Officer's second count has confirmed this row.
                $table->boolean('receiving_report_item_verified')->default(false);
            }
        });

        if (Schema::hasTable('back_orders_table')) {
            Schema::table('back_orders_table', function (Blueprint $table) {
                $columns = [
                    'back_order_replacement_item_id' => fn () => $table->unsignedBigInteger('back_order_replacement_item_id')->nullable(),
                    'back_order_replacement_quantity' => fn () => $table->unsignedInteger('back_order_replacement_quantity')->nullable(),
                    'back_order_replacement_unit_price' => fn () => $table->decimal('back_order_replacement_unit_price', 15, 2)->nullable(),
                    'back_order_replacement_reference' => fn () => $table->string('back_order_replacement_reference')->nullable(),
                    'back_order_replacement_files' => fn () => $table->text('back_order_replacement_files')->nullable(),
                    'back_order_cash_difference' => fn () => $table->decimal('back_order_cash_difference', 15, 2)->nullable(),
                    'back_order_cash_note' => fn () => $table->string('back_order_cash_note', 500)->nullable(),
                ];
                foreach ($columns as $column => $add) {
                    if (! Schema::hasColumn('back_orders_table', $column)) {
                        $add();
                    }
                }
            });
        }

        DB::table('receiving_report_items_table')
            ->whereIn('receiving_report_id', function ($q) {
                $q->select('receiving_report_id')
                    ->from('receiving_reports_table')
                    ->whereIn('receiving_report_status', ['Completed', 'Accepted']);
            })
            ->update(['receiving_report_item_verified' => 1]);
    }

    public function down(): void
    {
        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_verified')) {
                $table->dropColumn('receiving_report_item_verified');
            }
        });

        if (Schema::hasTable('back_orders_table')) {
            Schema::table('back_orders_table', function (Blueprint $table) {
                foreach ([
                    'back_order_replacement_item_id',
                    'back_order_replacement_quantity',
                    'back_order_replacement_unit_price',
                    'back_order_replacement_reference',
                    'back_order_replacement_files',
                    'back_order_cash_difference',
                    'back_order_cash_note',
                ] as $column) {
                    if (Schema::hasColumn('back_orders_table', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
