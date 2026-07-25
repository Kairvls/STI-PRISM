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
            // MESSAGE BEING REPLIED TO
            // =============================================

            $table->unsignedBigInteger('reply_to_message_id')
                ->nullable()
                ->after('sender_id');

            $table->foreign('reply_to_message_id')
                ->references('message_id')
                ->on('messages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {

            $table->dropForeign([
                'reply_to_message_id'
            ]);

            $table->dropColumn(
                'reply_to_message_id'
            );
        });
    }
};