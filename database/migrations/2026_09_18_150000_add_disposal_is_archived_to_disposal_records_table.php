<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('disposal_records_table')) {
            return;
        }

        if (! Schema::hasColumn('disposal_records_table', 'disposal_is_archived')) {
            Schema::table('disposal_records_table', function (Blueprint $table) {
                $table->boolean('disposal_is_archived')->default(false)->after('disposal_disposed_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('disposal_records_table')) {
            return;
        }

        if (Schema::hasColumn('disposal_records_table', 'disposal_is_archived')) {
            Schema::table('disposal_records_table', function (Blueprint $table) {
                $table->dropColumn('disposal_is_archived');
            });
        }
    }
};
