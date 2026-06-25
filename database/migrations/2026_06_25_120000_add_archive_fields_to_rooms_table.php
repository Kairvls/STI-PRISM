<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms_table', function (Blueprint $table): void {
            if (! Schema::hasColumn('rooms_table', 'room_is_archived')) {
                $table->boolean('room_is_archived')->default(false)->after('room_status');
            }

            if (! Schema::hasColumn('rooms_table', 'room_archived_at')) {
                $table->timestamp('room_archived_at')->nullable()->after('room_is_archived');
            }

            if (! Schema::hasColumn('rooms_table', 'room_archived_reason')) {
                $table->string('room_archived_reason')->nullable()->after('room_archived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rooms_table', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('rooms_table', 'room_archived_reason') ? 'room_archived_reason' : null,
                Schema::hasColumn('rooms_table', 'room_archived_at') ? 'room_archived_at' : null,
                Schema::hasColumn('rooms_table', 'room_is_archived') ? 'room_is_archived' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
