<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;


// =====================================================
// USER PRIVATE CHANNEL
// =====================================================

Broadcast::channel(
    'App.Models.User.{id}',
    function ($user, $id) {

        return (int) $user->user_id === (int) $id;

    }
);


// =====================================================
// PRIVATE CONVERSATION CHANNEL
//
// PURPOSE:
// Only users who are participants of the conversation
// are allowed to listen for real time messages.
// =====================================================

Broadcast::channel(
    'conversation.{conversationId}',
    function ($user, $conversationId) {

        // =============================================
        // CHECK CONVERSATION PARTICIPANT
        // =============================================

        return DB::table('conversation_participants')

            ->where(
                'conversation_id',
                $conversationId
            )

            ->where(
                'user_id',
                $user->user_id
            )

            ->exists();

    }
);

// =====================================================
// USER MESSAGE CHANNEL
//
// PURPOSE:
// Every logged in user gets their own channel.
//
// Example:
// user.5
//
// Only user ID 5 can listen to user.5.
// =====================================================

Broadcast::channel(
    'user.{userId}',
    function ($user, $userId) {

        return (int) $user->user_id === (int) $userId;

    }
);