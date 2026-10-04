<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('authority_to_purchase_table')
            || Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_ris_supplier_split')
        ) {
            return;
        }

        // Existing ATPs keep covering their whole RIS; only new per-supplier ATPs set this.
        Schema::table('authority_to_purchase_table', function (Blueprint $table) {
            $table->boolean('authority_purchase_ris_supplier_split')->default(false)->after('authority_purchase_supplier_id');
        });
    }

    public function down(): void
    {
        if (
            !Schema::hasTable('authority_to_purchase_table')
            || !Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_ris_supplier_split')
        ) {
            return;
        }

        Schema::table('authority_to_purchase_table', function (Blueprint $table) {
            $table->dropColumn('authority_purchase_ris_supplier_split');
        });
    }
};
