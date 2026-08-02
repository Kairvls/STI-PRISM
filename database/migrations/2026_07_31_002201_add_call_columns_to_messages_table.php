<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {

            // =============================================
            // MESSAGE TYPE
            // =============================================

            $table->enum('message_type', [

                'text',
                'call',

            ])->default('text')->after('message_content');

            // =============================================
            // RELATED CALL
            // =============================================

            $table->unsignedBigInteger('call_id')
                ->nullable()
                ->after('message_type');

            $table->foreign('call_id')
                ->references('call_id')
                ->on('calls')
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {

            $table->dropForeign(['call_id']);

            $table->dropColumn([
                'message_type',
                'call_id'
            ]);

        });
    }
};