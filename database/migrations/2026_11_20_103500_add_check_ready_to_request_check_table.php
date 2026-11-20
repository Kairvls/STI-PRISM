<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('request_check_table')) {
            return;
        }

        Schema::table('request_check_table', function (Blueprint $table) {
            if (! Schema::hasColumn('request_check_table', 'request_check_check_ready_at')) {
                $table->timestamp('request_check_check_ready_at')->nullable();
            }
            if (! Schema::hasColumn('request_check_table', 'request_check_check_ready_by')) {
                $table->unsignedBigInteger('request_check_check_ready_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('request_check_table')) {
            return;
        }

        Schema::table('request_check_table', function (Blueprint $table) {
            foreach (['request_check_check_ready_by', 'request_check_check_ready_at'] as $column) {
                if (Schema::hasColumn('request_check_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
