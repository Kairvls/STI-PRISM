<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users_table')) {
            return;
        }

        if (! Schema::hasColumn('users_table', 'user_can_procurement')) {
            Schema::table('users_table', function (Blueprint $table) {
                $table->boolean('user_can_procurement')->default(false)->after('user_role_id');
            });
        }

        // Preserve current access for existing Maintenance + Purchaser accounts.
        DB::table('users_table')
            ->whereIn('user_role_id', [2, 3])
            ->update(['user_can_procurement' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('users_table')) {
            return;
        }

        if (Schema::hasColumn('users_table', 'user_can_procurement')) {
            Schema::table('users_table', function (Blueprint $table) {
                $table->dropColumn('user_can_procurement');
            });
        }
    }
};
