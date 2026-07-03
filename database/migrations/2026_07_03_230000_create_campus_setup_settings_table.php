<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_setup_settings_table', function (Blueprint $table) {
            $table->bigIncrements('campus_setup_setting_id');
            $table->text('campus_setup_pin_hash')->nullable();
            $table->unsignedBigInteger('campus_setup_pin_updated_by')->nullable();
            $table->timestamp('campus_setup_pin_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_setup_settings_table');
    }
};