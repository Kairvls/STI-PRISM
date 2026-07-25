<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    // =====================================================
    // TYPING EVENT DATA
    // =====================================================

    public int $conversationId;
    public int $senderId;
    public int $receiverId;
    public bool $isTyping;


    // =====================================================
    // CREATE EVENT
    // =====================================================

    public function __construct(
        int $conversationId,
        int $senderId,
        int $receiverId,
        bool $isTyping
    ) {
        $this->conversationId = $conversationId;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->isTyping = $isTyping;
    }


    // =====================================================
    // SEND DIRECTLY TO RECEIVER'S GLOBAL USER CHANNEL
    //
    // Example:
    // user.5
    // =====================================================

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'user.' . $this->receiverId
            ),
        ];
    }


    // =====================================================
    // JAVASCRIPT EVENT NAME
    // =====================================================

    public function broadcastAs(): string
    {
        return 'user.typing';
    }


    // =====================================================
    // DATA SENT TO JAVASCRIPT
    // =====================================================

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->senderId,
            'is_typing' => $this->isTyping,
        ];
    }
}