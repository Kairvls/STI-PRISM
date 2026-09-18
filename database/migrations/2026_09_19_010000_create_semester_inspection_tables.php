<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('semester_inspection_campaigns_table')) {
            Schema::create('semester_inspection_campaigns_table', function (Blueprint $table) {
                $table->id('campaign_id');
                $table->string('campaign_title', 255);
                $table->string('campaign_academic_year', 32)->nullable();
                $table->string('campaign_semester', 32);
                $table->date('campaign_start_date')->nullable();
                $table->date('campaign_due_date');
                $table->string('campaign_scope_type', 32)->default('campus');
                $table->unsignedBigInteger('campaign_scope_building_id')->nullable();
                $table->unsignedBigInteger('campaign_scope_floor_id')->nullable();
                $table->string('campaign_status', 32)->default('Active');
                $table->text('campaign_notes')->nullable();
                $table->unsignedBigInteger('campaign_created_by')->nullable();
                $table->timestamp('campaign_completed_at')->nullable();
                $table->timestamp('campaign_created_at')->useCurrent();
                $table->timestamp('campaign_updated_at')->nullable();

                $table->index(['campaign_status', 'campaign_due_date'], 'sic_status_due_idx');
                $table->index('campaign_scope_building_id', 'sic_building_idx');
                $table->index('campaign_scope_floor_id', 'sic_floor_idx');
            });
        }

        if (! Schema::hasTable('semester_inspection_items_table')) {
            Schema::create('semester_inspection_items_table', function (Blueprint $table) {
                $table->id('item_id');
                $table->unsignedBigInteger('item_campaign_id');
                $table->unsignedBigInteger('item_equipment_id');
                $table->string('item_status', 32)->default('Pending');
                $table->string('item_condition', 32)->nullable();
                $table->text('item_findings')->nullable();
                $table->text('item_action_taken')->nullable();
                $table->string('item_proof_image', 255)->nullable();
                $table->string('item_inventory_status_applied', 64)->nullable();
                $table->string('item_condition_status_applied', 64)->nullable();
                $table->unsignedBigInteger('item_inspected_by')->nullable();
                $table->timestamp('item_inspected_at')->nullable();
                $table->timestamp('item_created_at')->useCurrent();
                $table->timestamp('item_updated_at')->nullable();

                $table->unique(['item_campaign_id', 'item_equipment_id'], 'sii_campaign_equipment_unique');
                $table->index(['item_campaign_id', 'item_status'], 'sii_campaign_status_idx');
                $table->index('item_equipment_id', 'sii_equipment_idx');
                $table->index('item_condition', 'sii_condition_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_inspection_items_table');
        Schema::dropIfExists('semester_inspection_campaigns_table');
    }
};
