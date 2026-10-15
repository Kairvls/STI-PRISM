<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'reporters_email_unique';

    public function up(): void
    {
        if ($this->hasIndex()) {
            return;
        }

        DB::table('reporters_table')
            ->whereNotNull('reporter_email_address')
            ->update(['reporter_email_address' => DB::raw("NULLIF(TRIM(reporter_email_address), '')")]);

        $duplicates = DB::table('reporters_table')
            ->whereNotNull('reporter_email_address')
            ->selectRaw('LOWER(reporter_email_address) as email, COUNT(*) as total')
            ->groupBy('email')
            ->having('total', '>', 1)
            ->pluck('email');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Reporters share these emails, give each reporter its own email first: '.$duplicates->implode(', ')
            );
        }

        Schema::table('reporters_table', function (Blueprint $table) {
            $table->unique('reporter_email_address', self::INDEX);
        });
    }

    public function down(): void
    {
        if (! $this->hasIndex()) {
            return;
        }

        Schema::table('reporters_table', function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });
    }

    private function hasIndex(): bool
    {
        return collect(Schema::getIndexes('reporters_table'))->contains('name', self::INDEX);
    }
};
