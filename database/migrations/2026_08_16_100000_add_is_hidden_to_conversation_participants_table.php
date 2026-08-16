<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('conversation_participants', 'is_hidden')) {
            Schema::table('conversation_participants', function (Blueprint $table) {
                $table->boolean('is_hidden')->default(false)->after('last_read_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('conversation_participants', 'is_hidden')) {
            Schema::table('conversation_participants', function (Blueprint $table) {
                $table->dropColumn('is_hidden');
            });
        }
    }
};
