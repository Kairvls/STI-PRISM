<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_disposal_requests_table')) {
            return;
        }

        if (! Schema::hasColumn('equipment_disposal_requests_table', 'disposal_request_room_id')) {
            Schema::table('equipment_disposal_requests_table', function (Blueprint $table) {
                $table->unsignedBigInteger('disposal_request_room_id')->nullable()->after('disposal_request_location');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipment_disposal_requests_table')) {
            return;
        }

        if (Schema::hasColumn('equipment_disposal_requests_table', 'disposal_request_room_id')) {
            Schema::table('equipment_disposal_requests_table', function (Blueprint $table) {
                $table->dropColumn('disposal_request_room_id');
            });
        }
    }
};
