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

        Schema::table('users_table', function (Blueprint $table) {
            if (! Schema::hasColumn('users_table', 'user_away_status')) {
                $table->string('user_away_status', 20)
                    ->default('available')
                    ->after('user_status');
            }
            if (! Schema::hasColumn('users_table', 'user_away_until')) {
                $table->date('user_away_until')
                    ->nullable()
                    ->after('user_away_status');
            }
            if (! Schema::hasColumn('users_table', 'user_away_note')) {
                $table->string('user_away_note', 255)
                    ->nullable()
                    ->after('user_away_until');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users_table')) {
            return;
        }

        Schema::table('users_table', function (Blueprint $table) {
            foreach (['user_away_note', 'user_away_until', 'user_away_status'] as $column) {
                if (Schema::hasColumn('users_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
