<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('authority_to_purchase_table')) {
            return;
        }

        Schema::table('authority_to_purchase_table', function (Blueprint $table) {
            if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_authorized_by')) {
                $table->unsignedBigInteger('authority_purchase_authorized_by')->nullable()->after('authority_purchase_authorized_by_signature');
            }
            if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_authorized_by_name')) {
                $table->string('authority_purchase_authorized_by_name', 255)->nullable()->after('authority_purchase_authorized_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('authority_to_purchase_table')) {
            return;
        }

        Schema::table('authority_to_purchase_table', function (Blueprint $table) {
            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_authorized_by_name')) {
                $table->dropColumn('authority_purchase_authorized_by_name');
            }
            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_authorized_by')) {
                $table->dropColumn('authority_purchase_authorized_by');
            }
        });
    }
};
