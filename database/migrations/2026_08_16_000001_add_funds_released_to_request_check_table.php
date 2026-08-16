<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('request_check_table')) {
            return;
        }

        Schema::table('request_check_table', function (Blueprint $table) {
            $this->addColumnIfMissing(
                $table,
                'request_check_funds_released_at',
                fn () => $table->dateTime('request_check_funds_released_at')->nullable()->after('request_check_approved_at')
            );
            $this->addColumnIfMissing(
                $table,
                'request_check_funds_released_by',
                fn () => $table->bigInteger('request_check_funds_released_by')->nullable()->after('request_check_funds_released_at')
            );
        });

        DB::table('request_check_table')
            ->where('request_check_status', 'Approved')
            ->whereNull('request_check_funds_released_at')
            ->update([
                'request_check_funds_released_at' => DB::raw('COALESCE(request_check_approved_at, request_check_updated_at, request_check_created_at)'),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('request_check_table')) {
            return;
        }

        Schema::table('request_check_table', function (Blueprint $table) {
            if (Schema::hasColumn('request_check_table', 'request_check_funds_released_by')) {
                $table->dropColumn('request_check_funds_released_by');
            }
            if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
                $table->dropColumn('request_check_funds_released_at');
            }
        });
    }

    private function addColumnIfMissing(Blueprint $table, string $column, callable $definition): void
    {
        if (!Schema::hasColumn('request_check_table', $column)) {
            $definition();
        }
    }
};
