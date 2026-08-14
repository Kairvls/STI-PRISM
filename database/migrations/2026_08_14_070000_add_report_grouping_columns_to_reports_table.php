<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports_table', function (Blueprint $table) {
            $table->unsignedInteger('report_related_count')
                ->default(1)
                ->after('report_is_archived');

            $table->text('report_related_notes')
                ->nullable()
                ->after('report_related_count');
        });
    }

    public function down(): void
    {
        Schema::table('reports_table', function (Blueprint $table) {
            $table->dropColumn([
                'report_related_count',
                'report_related_notes',
            ]);
        });
    }
};
