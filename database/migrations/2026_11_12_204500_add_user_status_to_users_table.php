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

        if (! Schema::hasColumn('users_table', 'user_status')) {
            Schema::table('users_table', function (Blueprint $table) {
                $table->string('user_status', 20)
                    ->default('Active')
                    ->after('user_contact_number');
            });
        }

        DB::table('users_table')
            ->where(function ($query) {
                $query->whereNull('user_status')->orWhere('user_status', '');
            })
            ->update(['user_status' => 'Active']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('users_table')) {
            return;
        }

        if (Schema::hasColumn('users_table', 'user_status')) {
            Schema::table('users_table', function (Blueprint $table) {
                $table->dropColumn('user_status');
            });
        }
    }
};
