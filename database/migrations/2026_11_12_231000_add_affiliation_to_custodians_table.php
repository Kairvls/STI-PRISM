<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custodians_table')) {
            return;
        }

        Schema::table('custodians_table', function (Blueprint $table) {
            if (! Schema::hasColumn('custodians_table', 'custodian_affiliation')) {
                $table->string('custodian_affiliation', 20)->default('Campus')->after('custodian_status');
                $table->index('custodian_affiliation', 'cust_affiliation_idx');
            }
            if (! Schema::hasColumn('custodians_table', 'custodian_organization')) {
                $table->string('custodian_organization', 150)->nullable()->after('custodian_affiliation');
            }
        });

        if (Schema::hasColumn('custodians_table', 'custodian_affiliation')) {
            DB::table('custodians_table')
                ->whereNull('custodian_affiliation')
                ->orWhere('custodian_affiliation', '')
                ->update(['custodian_affiliation' => 'Campus']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('custodians_table')) {
            return;
        }

        Schema::table('custodians_table', function (Blueprint $table) {
            if (Schema::hasColumn('custodians_table', 'custodian_organization')) {
                $table->dropColumn('custodian_organization');
            }
            if (Schema::hasColumn('custodians_table', 'custodian_affiliation')) {
                $table->dropIndex('cust_affiliation_idx');
                $table->dropColumn('custodian_affiliation');
            }
        });
    }
};
