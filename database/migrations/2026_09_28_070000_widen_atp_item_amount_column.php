<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('authority_to_purchase_items_table', 'atp_amount')) {
            return;
        }

        // Quantity (7 digits) x unit price (7 digits + 2 decimals) can reach 14 whole digits.
        Schema::table('authority_to_purchase_items_table', function (Blueprint $table) {
            $table->decimal('atp_amount', 16, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('authority_to_purchase_items_table', 'atp_amount')) {
            return;
        }

        Schema::table('authority_to_purchase_items_table', function (Blueprint $table) {
            $table->decimal('atp_amount', 12, 2)->nullable()->change();
        });
    }
};
