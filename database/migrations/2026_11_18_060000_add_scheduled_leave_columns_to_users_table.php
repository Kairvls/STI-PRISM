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

        Schema::table('users_table', function (Blueprint $table) {
            if (! Schema::hasColumn('users_table', 'user_away_starts_at')) {
                $table->dateTime('user_away_starts_at')
                    ->nullable()
                    ->after('user_away_status');
            }
            if (! Schema::hasColumn('users_table', 'user_away_reminder_sent_at')) {
                $table->dateTime('user_away_reminder_sent_at')
                    ->nullable()
                    ->after('user_away_note');
            }
        });

        // Promote legacy date-only "back on" to end-of-day datetime, and backfill start = now when away.
        if (Schema::hasColumn('users_table', 'user_away_until')) {
            try {
                $rows = DB::table('users_table')
                    ->whereNotNull('user_away_until')
                    ->whereIn('user_away_status', ['leave', 'absent'])
                    ->get(['user_id', 'user_away_until', 'user_away_starts_at']);

                foreach ($rows as $row) {
                    $until = $row->user_away_until;
                    if ($until === null || $until === '') {
                        continue;
                    }

                    $end = strlen((string) $until) <= 10
                        ? $until.' 08:00:00'
                        : (string) $until;

                    $update = ['user_away_until' => $end];
                    if (blank($row->user_away_starts_at ?? null)) {
                        $update['user_away_starts_at'] = now()->format('Y-m-d H:i:s');
                    }

                    DB::table('users_table')
                        ->where('user_id', $row->user_id)
                        ->update($update);
                }
            } catch (\Throwable) {
                // Best-effort backfill.
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users_table')) {
            return;
        }

        Schema::table('users_table', function (Blueprint $table) {
            foreach (['user_away_reminder_sent_at', 'user_away_starts_at'] as $column) {
                if (Schema::hasColumn('users_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
