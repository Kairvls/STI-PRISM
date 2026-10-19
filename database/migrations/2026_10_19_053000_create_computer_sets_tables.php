<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('computer_sets_table')) {
            Schema::create('computer_sets_table', function (Blueprint $table) {
                $table->id('computer_set_id');
                $table->string('computer_set_tag', 40)->unique();
                $table->string('computer_set_name', 120)->nullable();
                $table->unsignedBigInteger('computer_set_room_id')->nullable()->index();
                $table->string('computer_set_placement_zone', 120)->nullable();
                $table->string('computer_set_status', 20)->default('Active')->index();
                $table->unsignedBigInteger('computer_set_created_by')->nullable();
                $table->timestamp('computer_set_created_at')->nullable();
                $table->timestamp('computer_set_updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('computer_set_members_table')) {
            Schema::create('computer_set_members_table', function (Blueprint $table) {
                $table->id('computer_set_member_id');
                $table->unsignedBigInteger('computer_set_id')->index();
                $table->unsignedBigInteger('equipment_id')->unique();
                $table->string('member_role', 40)->nullable()->index();
                $table->unsignedSmallInteger('member_sort')->default(0);
                $table->timestamp('member_added_at')->nullable();
                $table->unsignedBigInteger('member_added_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('computer_set_members_table');
        Schema::dropIfExists('computer_sets_table');
    }
};
