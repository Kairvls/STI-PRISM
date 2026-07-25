<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDelivered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $conversation_id;
    public int $message_id;
    public int $delivered_by;
    public string $delivered_at;

    public function __construct(
        int $conversationId,
        int $messageId,
        int $deliveredBy,
        string $deliveredAt
    ) {
        $this->conversation_id = $conversationId;
        $this->message_id = $messageId;
        $this->delivered_by = $deliveredBy;
        $this->delivered_at = $deliveredAt;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'conversation.' . $this->conversation_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.delivered';
    }
}