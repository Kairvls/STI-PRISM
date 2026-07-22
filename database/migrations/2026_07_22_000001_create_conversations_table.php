<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id('conversation_id');
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id('participant_id');
            $table->unsignedBigInteger('conversation_id');
            $table->bigInteger('user_id');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('conversation_id');
            $table->bigInteger('sender_id');
            $table->text('message_content');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('conversation_id')->references('conversation_id')->on('conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('user_id')->on('users_table')->cascadeOnDelete();
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->foreign('conversation_id')->references('conversation_id')->on('conversations')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users_table')->cascadeOnDelete();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreign('last_message_id')->references('message_id')->on('messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'last_message_id')) {
                $table->dropForeign(['last_message_id']);
            }
            $table->dropIfExists('conversations');
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropForeign(['user_id']);
            $table->dropIfExists('conversation_participants');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropForeign(['sender_id']);
            $table->dropIfExists('messages');
        });
    }
};
