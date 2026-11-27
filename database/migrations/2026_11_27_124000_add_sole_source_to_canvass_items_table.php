<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('canvass_items_table')) {
            return;
        }

        Schema::table('canvass_items_table', function (Blueprint $table) {
            if (! Schema::hasColumn('canvass_items_table', 'canvass_item_is_sole_source')) {
                $table->boolean('canvass_item_is_sole_source')->default(false)->after('canvass_item_quantity');
            }
            if (! Schema::hasColumn('canvass_items_table', 'canvass_item_sole_source_reason')) {
                $table->text('canvass_item_sole_source_reason')->nullable()->after('canvass_item_is_sole_source');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('canvass_items_table')) {
            return;
        }

        Schema::table('canvass_items_table', function (Blueprint $table) {
            if (Schema::hasColumn('canvass_items_table', 'canvass_item_sole_source_reason')) {
                $table->dropColumn('canvass_item_sole_source_reason');
            }
            if (Schema::hasColumn('canvass_items_table', 'canvass_item_is_sole_source')) {
                $table->dropColumn('canvass_item_is_sole_source');
            }
        });
    }
};
