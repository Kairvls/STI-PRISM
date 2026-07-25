<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'message_reactions',
            function (Blueprint $table) {

                $table->bigIncrements(
                    'message_reaction_id'
                );


                // =============================================
                // MESSAGE BEING REACTED TO
                //
                // MUST MATCH:
                // messages.message_id = BIGINT UNSIGNED
                // =============================================

                $table->unsignedBigInteger(
                    'message_id'
                );


                // =============================================
                // USER WHO REACTED
                //
                // MUST MATCH:
                // users_table.user_id = BIGINT
                //
                // IMPORTANT:
                // This is SIGNED, not unsigned.
                // =============================================

                $table->bigInteger(
                    'user_id'
                );


                // =============================================
                // REACTION TYPE
                //
                // like  = 👍
                // heart = ❤️
                // check = ✓
                // =============================================

                $table->string(
                    'reaction',
                    20
                );


                $table->timestamps();


                // =============================================
                // ONE REACTION PER USER PER MESSAGE
                //
                // Example:
                //
                // User reacts 👍
                // then changes to ❤️
                //
                // We UPDATE the existing reaction instead
                // of creating another row.
                // =============================================

                $table->unique(
                    [
                        'message_id',
                        'user_id',
                    ],
                    'unique_message_user_reaction'
                );


                // =============================================
                // MESSAGE FOREIGN KEY
                // =============================================

                $table->foreign(
                    'message_id'
                )
                    ->references('message_id')
                    ->on('messages')
                    ->cascadeOnDelete();


                // =============================================
                // USER FOREIGN KEY
                // =============================================

                $table->foreign(
                    'user_id'
                )
                    ->references('user_id')
                    ->on('users_table')
                    ->cascadeOnDelete();
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'message_reactions'
        );
    }
};