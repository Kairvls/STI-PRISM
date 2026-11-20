<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        if (! Schema::hasColumn('borrowing_records_table', 'borrowing_source')) {
            Schema::table('borrowing_records_table', function (Blueprint $table) {
                $table->string('borrowing_source', 20)
                    ->default('admin')
                    ->after('borrowing_status');
            });
        }

        // Existing rows stay desk/admin unless already tagged.
        DB::table('borrowing_records_table')
            ->where(function ($q) {
                $q->whereNull('borrowing_source')->orWhere('borrowing_source', '');
            })
            ->update(['borrowing_source' => 'admin']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        if (Schema::hasColumn('borrowing_records_table', 'borrowing_source')) {
            Schema::table('borrowing_records_table', function (Blueprint $table) {
                $table->dropColumn('borrowing_source');
            });
        }
    }
};
