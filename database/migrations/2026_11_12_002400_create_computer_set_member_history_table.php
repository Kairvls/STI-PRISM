<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('computer_set_member_history_table')) {
            return;
        }

        Schema::create('computer_set_member_history_table', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('computer_set_id')->index();
            $table->string('member_role', 40)->nullable()->index();
            $table->unsignedBigInteger('equipment_id')->index();
            $table->string('event', 20)->index(); // added | removed | replaced
            $table->unsignedBigInteger('replaced_by_equipment_id')->nullable()->index();
            $table->string('notes', 255)->nullable();
            $table->timestamp('performed_at')->nullable()->index();
            $table->unsignedBigInteger('performed_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('computer_set_member_history_table');
    }
};
