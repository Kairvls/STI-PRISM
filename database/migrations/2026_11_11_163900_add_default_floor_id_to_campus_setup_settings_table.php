<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campus_setup_settings_table')) {
            return;
        }

        Schema::table('campus_setup_settings_table', function (Blueprint $table) {
            if (! Schema::hasColumn('campus_setup_settings_table', 'default_floor_id')) {
                $table->unsignedBigInteger('default_floor_id')->nullable()->after('campus_setup_pin_updated_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('campus_setup_settings_table')) {
            return;
        }

        Schema::table('campus_setup_settings_table', function (Blueprint $table) {
            if (Schema::hasColumn('campus_setup_settings_table', 'default_floor_id')) {
                $table->dropColumn('default_floor_id');
            }
        });
    }
};
