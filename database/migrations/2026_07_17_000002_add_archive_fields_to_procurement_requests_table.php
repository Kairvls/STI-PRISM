<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_requests_table', function (Blueprint $table) {
            if (!Schema::hasColumn('procurement_requests_table', 'procurement_request_is_archived')) {
                $table->boolean('procurement_request_is_archived')->default(false)->after('procurement_request_created_at');
            }

            if (!Schema::hasColumn('procurement_requests_table', 'procurement_request_archived_at')) {
                $table->dateTime('procurement_request_archived_at')->nullable()->after('procurement_request_is_archived');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_requests_table', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_requests_table', 'procurement_request_archived_at')) {
                $table->dropColumn('procurement_request_archived_at');
            }

            if (Schema::hasColumn('procurement_requests_table', 'procurement_request_is_archived')) {
                $table->dropColumn('procurement_request_is_archived');
            }
        });
    }
};
