<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReactionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    // =====================================================
    // REACTION DATA
    // =====================================================

    public int $conversationId;
    public int $messageId;
    public array $reactions;


    // =====================================================
    // CREATE EVENT
    // =====================================================

    public function __construct(
        int $conversationId,
        int $messageId,
        array $reactions
    ) {
        $this->conversationId =
            $conversationId;

        $this->messageId =
            $messageId;

        $this->reactions =
            $reactions;
    }


    // =====================================================
    // PRIVATE CONVERSATION CHANNEL
    //
    // Everyone participating in this conversation
    // receives the reaction update.
    // =====================================================

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'conversation.' .
                $this->conversationId
            ),
        ];
    }


    // =====================================================
    // JAVASCRIPT EVENT NAME
    // =====================================================

    public function broadcastAs(): string
    {
        return 'message.reaction.updated';
    }


    // =====================================================
    // DATA SENT THROUGH REVERB
    // =====================================================

    public function broadcastWith(): array
    {
        return [
            'conversation_id' =>
                $this->conversationId,

            'message_id' =>
                $this->messageId,

            'reactions' =>
                $this->reactions,
        ];
    }
}