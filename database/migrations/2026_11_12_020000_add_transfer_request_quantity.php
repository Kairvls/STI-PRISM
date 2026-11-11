<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_transfer_requests_table')) {
            return;
        }

        if (! Schema::hasColumn('equipment_transfer_requests_table', 'transfer_request_quantity')) {
            Schema::table('equipment_transfer_requests_table', function (Blueprint $table) {
                $table->unsignedInteger('transfer_request_quantity')->nullable()->after('transfer_request_equipment_ids');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipment_transfer_requests_table')) {
            return;
        }

        if (Schema::hasColumn('equipment_transfer_requests_table', 'transfer_request_quantity')) {
            Schema::table('equipment_transfer_requests_table', function (Blueprint $table) {
                $table->dropColumn('transfer_request_quantity');
            });
        }
    }
};
