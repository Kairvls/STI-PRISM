<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_table', function (Blueprint $table) {

            // =========================================
            // USER ONLINE / OFFLINE TRACKING
            // =========================================

            $table->timestamp('last_active_at')
                ->nullable()
                ->after('user_password');
        });
    }

    public function down(): void
    {
        Schema::table('users_table', function (Blueprint $table) {

            // =========================================
            // REMOVE ACTIVITY TRACKING
            // =========================================

            $table->dropColumn('last_active_at');
        });
    }
};