<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brands_table')) {
            return;
        }

        Schema::table('brands_table', function (Blueprint $table) {
            if (! Schema::hasColumn('brands_table', 'brand_avoid_ordering')) {
                $table->boolean('brand_avoid_ordering')->default(false)->after('brand_status');
            }
            if (! Schema::hasColumn('brands_table', 'brand_caution_note')) {
                $table->string('brand_caution_note', 255)->nullable()->after('brand_avoid_ordering');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('brands_table')) {
            return;
        }

        Schema::table('brands_table', function (Blueprint $table) {
            if (Schema::hasColumn('brands_table', 'brand_caution_note')) {
                $table->dropColumn('brand_caution_note');
            }
            if (Schema::hasColumn('brands_table', 'brand_avoid_ordering')) {
                $table->dropColumn('brand_avoid_ordering');
            }
        });
    }
};
