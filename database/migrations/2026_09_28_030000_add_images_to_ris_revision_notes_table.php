<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ris_revision_notes_table')) {
            return;
        }

        Schema::table('ris_revision_notes_table', function (Blueprint $table) {
            if (!Schema::hasColumn('ris_revision_notes_table', 'ris_revision_images')) {
                // JSON list of {path, name} for up to 3 proof images attached to the revision remarks.
                $table->text('ris_revision_images')
                    ->nullable()
                    ->after('ris_revision_note');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ris_revision_notes_table')) {
            return;
        }

        Schema::table('ris_revision_notes_table', function (Blueprint $table) {
            if (Schema::hasColumn('ris_revision_notes_table', 'ris_revision_images')) {
                $table->dropColumn('ris_revision_images');
            }
        });
    }
};
