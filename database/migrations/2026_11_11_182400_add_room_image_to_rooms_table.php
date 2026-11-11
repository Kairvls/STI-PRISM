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

        if (! Schema::hasColumn('rooms_table', 'room_image')) {
            Schema::table('rooms_table', function (Blueprint $table) {
                $table->string('room_image', 500)->nullable()->after('room_color');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rooms_table')) {
            return;
        }

        if (Schema::hasColumn('rooms_table', 'room_image')) {
            Schema::table('rooms_table', function (Blueprint $table) {
                $table->dropColumn('room_image');
            });
        }
    }
};
