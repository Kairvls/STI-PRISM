<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers_table')) {
            return;
        }

        Schema::table('suppliers_table', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers_table', 'supplier_logo_path')) {
                $table->string('supplier_logo_path', 500)->nullable()->after('supplier_check_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('suppliers_table')) {
            return;
        }

        Schema::table('suppliers_table', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers_table', 'supplier_logo_path')) {
                $table->dropColumn('supplier_logo_path');
            }
        });
    }
};
