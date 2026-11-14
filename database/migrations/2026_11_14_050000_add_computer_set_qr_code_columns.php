<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('computer_sets_table')) {
            return;
        }

        Schema::table('computer_sets_table', function (Blueprint $table) {
            if (! Schema::hasColumn('computer_sets_table', 'computer_set_qr_code')) {
                $table->string('computer_set_qr_code', 80)->nullable()->unique()->after('computer_set_tag');
            }
            if (! Schema::hasColumn('computer_sets_table', 'computer_set_qr_issued_at')) {
                $table->timestamp('computer_set_qr_issued_at')->nullable()->after('computer_set_qr_code');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('computer_sets_table')) {
            return;
        }

        Schema::table('computer_sets_table', function (Blueprint $table) {
            if (Schema::hasColumn('computer_sets_table', 'computer_set_qr_issued_at')) {
                $table->dropColumn('computer_set_qr_issued_at');
            }
            if (Schema::hasColumn('computer_sets_table', 'computer_set_qr_code')) {
                $table->dropUnique(['computer_set_qr_code']);
                $table->dropColumn('computer_set_qr_code');
            }
        });
    }
};
