<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {

            $table->id('call_id');

            // conversations.conversation_id is BIGINT UNSIGNED
            $table->unsignedBigInteger('conversation_id');

            // users_table.user_id is SIGNED BIGINT
            $table->bigInteger('caller_id');

            $table->bigInteger('receiver_id');

            $table->enum('call_type', [
                'audio',
                'video',
            ]);

            $table->enum('status', [
                'calling',
                'ringing',
                'accepted',
                'declined',
                'ended',
                'missed',
                'busy',
            ])->default('calling');

            $table->timestamp('started_at')->nullable();

            $table->timestamp('answered_at')->nullable();

            $table->timestamp('ended_at')->nullable();

            $table->integer('duration')->default(0);

            $table->timestamps();

            // Foreign key to conversations
            $table->foreign('conversation_id')
                ->references('conversation_id')
                ->on('conversations')
                ->cascadeOnDelete();

            // Foreign keys to users_table
            $table->foreign('caller_id')
                ->references('user_id')
                ->on('users_table')
                ->cascadeOnDelete();

            $table->foreign('receiver_id')
                ->references('user_id')
                ->on('users_table')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};