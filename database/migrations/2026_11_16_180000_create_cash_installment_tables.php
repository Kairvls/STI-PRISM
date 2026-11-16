<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('installment_terms_table')) {
            Schema::create('installment_terms_table', function (Blueprint $table) {
                $table->bigIncrements('term_id');
                $table->string('term_name', 120);
                $table->unsignedSmallInteger('term_months');
                $table->boolean('term_is_active')->default(true);
                $table->timestamp('term_created_at')->nullable();
                $table->timestamp('term_updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('cash_installment_plans_table')) {
            Schema::create('cash_installment_plans_table', function (Blueprint $table) {
                $table->bigIncrements('plan_id');
                $table->unsignedBigInteger('authority_purchase_id');
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedSmallInteger('plan_months');
                $table->decimal('plan_atp_total', 14, 2);
                $table->decimal('plan_downpayment', 14, 2)->default(0);
                $table->decimal('plan_remaining', 14, 2);
                $table->unsignedBigInteger('plan_created_by')->nullable();
                $table->timestamp('plan_created_at')->nullable();
                $table->timestamp('plan_updated_at')->nullable();
                $table->index('authority_purchase_id', 'cip_atp_idx');
            });
        }

        if (! Schema::hasTable('cash_installment_plan_items_table')) {
            Schema::create('cash_installment_plan_items_table', function (Blueprint $table) {
                $table->bigIncrements('item_id');
                $table->unsignedBigInteger('plan_id');
                $table->unsignedBigInteger('request_check_id')->nullable();
                $table->string('item_kind', 20); // downpayment | installment
                $table->unsignedSmallInteger('item_sequence')->default(1);
                $table->decimal('item_amount', 14, 2);
                $table->date('item_due_date')->nullable();
                $table->timestamp('item_created_at')->nullable();
                $table->index('plan_id', 'cipi_plan_idx');
                $table->index('request_check_id', 'cipi_rfc_idx');
            });
        }

        if (Schema::hasTable('installment_terms_table')
            && DB::table('installment_terms_table')->count() === 0) {
            $now = now();
            foreach ([
                ['Every 3 months', 3],
                ['Every 4 months', 4],
                ['Every 6 months', 6],
                ['Every 12 months', 12],
            ] as [$name, $months]) {
                DB::table('installment_terms_table')->insert([
                    'term_name' => $name,
                    'term_months' => $months,
                    'term_is_active' => 1,
                    'term_created_at' => $now,
                    'term_updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_installment_plan_items_table');
        Schema::dropIfExists('cash_installment_plans_table');
        Schema::dropIfExists('installment_terms_table');
    }
};
