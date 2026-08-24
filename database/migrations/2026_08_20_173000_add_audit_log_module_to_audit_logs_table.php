<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs_table')) {
            return;
        }

        if (Schema::hasColumn('audit_logs_table', 'audit_log_module')) {
            return;
        }

        Schema::table('audit_logs_table', function (Blueprint $table) {
            $table->string('audit_log_module')
                ->nullable()
                ->after('audit_log_action');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_logs_table')) {
            return;
        }

        if (!Schema::hasColumn('audit_logs_table', 'audit_log_module')) {
            return;
        }

        Schema::table('audit_logs_table', function (Blueprint $table) {
            $table->dropColumn('audit_log_module');
        });
    }
};
