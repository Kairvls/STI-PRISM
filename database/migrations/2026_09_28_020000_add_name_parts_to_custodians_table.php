<?php

use App\Support\ReporterImport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custodians_table')) {
            return;
        }

        Schema::table('custodians_table', function (Blueprint $table) {
            if (! Schema::hasColumn('custodians_table', 'custodian_first_name')) {
                $table->string('custodian_first_name', 100)->nullable()->after('custodian_full_name');
            }
            if (! Schema::hasColumn('custodians_table', 'custodian_middle_name')) {
                $table->string('custodian_middle_name', 100)->nullable()->after('custodian_first_name');
            }
            if (! Schema::hasColumn('custodians_table', 'custodian_last_name')) {
                $table->string('custodian_last_name', 100)->nullable()->after('custodian_middle_name');
            }
        });

        $reporterNames = Schema::hasTable('reporters_table') && ReporterImport::hasNameColumns()
            ? DB::table('reporters_table')
                ->whereNotNull('reporter_first_name')
                ->get(['reporter_id', 'reporter_first_name', 'reporter_middle_name', 'reporter_last_name'])
                ->keyBy('reporter_id')
            : collect();

        DB::table('custodians_table')
            ->whereNull('custodian_first_name')
            ->orderBy('custodian_id')
            ->get(['custodian_id', 'custodian_full_name', 'custodian_reporter_id'])
            ->each(function ($person) use ($reporterNames) {
                $reporter = $person->custodian_reporter_id ? $reporterNames->get($person->custodian_reporter_id) : null;
                $parts = $reporter && trim((string) $reporter->reporter_first_name) !== ''
                    ? [
                        'first' => trim((string) $reporter->reporter_first_name),
                        'middle' => trim((string) $reporter->reporter_middle_name),
                        'last' => trim((string) $reporter->reporter_last_name),
                    ]
                    : ReporterImport::splitFullName($person->custodian_full_name);

                DB::table('custodians_table')
                    ->where('custodian_id', $person->custodian_id)
                    ->update([
                        'custodian_first_name' => $parts['first'] !== '' ? $parts['first'] : null,
                        'custodian_middle_name' => $parts['middle'] !== '' ? $parts['middle'] : null,
                        'custodian_last_name' => $parts['last'] !== '' ? $parts['last'] : null,
                    ]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('custodians_table')) {
            return;
        }

        Schema::table('custodians_table', function (Blueprint $table) {
            foreach (['custodian_last_name', 'custodian_middle_name', 'custodian_first_name'] as $column) {
                if (Schema::hasColumn('custodians_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
