<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ris_approval_budget_settings_table')) {
            Schema::create('ris_approval_budget_settings_table', function (Blueprint $table) {
                $table->increments('ris_approval_budget_setting_id');
                $table->decimal('direct_approve_limit', 14, 2)->default(10000.00);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamp('updated_at')->nullable();
            });

            DB::table('ris_approval_budget_settings_table')->insert([
                'direct_approve_limit' => 10000.00,
                'updated_by' => null,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ris_approval_budget_settings_table');
    }
};
