<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users_table')) {
            return;
        }

        if (! Schema::hasColumn('users_table', 'user_profile_picture')) {
            Schema::table('users_table', function (Blueprint $table) {
                $table->string('user_profile_picture')->nullable()->after('user_contact_number');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users_table')) {
            return;
        }

        if (Schema::hasColumn('users_table', 'user_profile_picture')) {
            Schema::table('users_table', function (Blueprint $table) {
                $table->dropColumn('user_profile_picture');
            });
        }
    }
};
