<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('back_orders_table')) {
            return;
        }

        Schema::table('back_orders_table', function (Blueprint $table) {
            if (! Schema::hasColumn('back_orders_table', 'back_order_expected_delivery_date')) {
                $table->date('back_order_expected_delivery_date')->nullable()->after('back_order_remarks');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('back_orders_table')) {
            return;
        }

        Schema::table('back_orders_table', function (Blueprint $table) {
            if (Schema::hasColumn('back_orders_table', 'back_order_expected_delivery_date')) {
                $table->dropColumn('back_order_expected_delivery_date');
            }
        });
    }
};
