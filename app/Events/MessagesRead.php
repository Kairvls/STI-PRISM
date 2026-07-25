<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    // =====================================================
    // READ RECEIPT DATA
    // =====================================================

    public int $conversationId;
    public int $readerId;
    public array $messageIds;


    // =====================================================
    // CREATE EVENT
    // =====================================================

    public function __construct(
        int $conversationId,
        int $readerId,
        array $messageIds
    ) {
        $this->conversationId = $conversationId;
        $this->readerId = $readerId;
        $this->messageIds = $messageIds;
    }


    // =====================================================
    // PRIVATE CONVERSATION CHANNEL
    // =====================================================

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'conversation.' . $this->conversationId
            ),
        ];
    }


    // =====================================================
    // JAVASCRIPT EVENT NAME
    // =====================================================

    public function broadcastAs(): string
    {
        return 'messages.read';
    }


    // =====================================================
    // DATA SENT THROUGH REVERB
    // =====================================================

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'reader_id' => $this->readerId,
            'message_ids' => $this->messageIds,
        ];
    }
}