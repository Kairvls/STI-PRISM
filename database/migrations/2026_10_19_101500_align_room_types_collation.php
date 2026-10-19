<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('room_types_table')) {
            return;
        }

        // Match the rest of the app (rooms_table uses utf8mb4_unicode_ci).
        DB::statement('ALTER TABLE room_types_table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        // No-op: reverting collation is unnecessary.
    }
};
