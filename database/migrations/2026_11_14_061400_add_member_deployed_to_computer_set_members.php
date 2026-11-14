<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('computer_set_members_table')) {
            return;
        }

        Schema::table('computer_set_members_table', function (Blueprint $table) {
            if (! Schema::hasColumn('computer_set_members_table', 'member_is_deployed')) {
                $table->boolean('member_is_deployed')->default(false)->after('member_sort');
            }
            if (! Schema::hasColumn('computer_set_members_table', 'member_deployed_at')) {
                $table->timestamp('member_deployed_at')->nullable()->after('member_is_deployed');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('computer_set_members_table')) {
            return;
        }

        Schema::table('computer_set_members_table', function (Blueprint $table) {
            if (Schema::hasColumn('computer_set_members_table', 'member_deployed_at')) {
                $table->dropColumn('member_deployed_at');
            }
            if (Schema::hasColumn('computer_set_members_table', 'member_is_deployed')) {
                $table->dropColumn('member_is_deployed');
            }
        });
    }
};
