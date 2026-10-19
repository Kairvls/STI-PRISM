<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms_table')) {
            return;
        }

        if (! Schema::hasColumn('rooms_table', 'room_department_id')) {
            Schema::table('rooms_table', function (Blueprint $table) {
                $table->unsignedBigInteger('room_department_id')
                    ->nullable()
                    ->after('room_type')
                    ->index('rooms_department_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rooms_table')) {
            return;
        }

        if (Schema::hasColumn('rooms_table', 'room_department_id')) {
            Schema::table('rooms_table', function (Blueprint $table) {
                $table->dropIndex('rooms_department_idx');
                $table->dropColumn('room_department_id');
            });
        }
    }
};
