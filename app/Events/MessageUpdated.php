<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Message $message;

    public string $action;


    // =====================================================
    // CREATE EVENT
    // =====================================================

    public function __construct(
        Message $message,
        string $action
    ) {
        $this->message = $message;
        $this->action = $action;
    }


    // =====================================================
    // PRIVATE CONVERSATION CHANNEL
    // =====================================================

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'conversation.' .
                $this->message->conversation_id
            ),
        ];
    }


    // =====================================================
    // EVENT NAME RECEIVED BY JAVASCRIPT
    // =====================================================

    public function broadcastAs(): string
    {
        return 'message.updated';
    }


    // =====================================================
    // DATA SENT TO OTHER USER
    // =====================================================

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,

            'message' => [
                'message_id' =>
                    $this->message->message_id,

                'conversation_id' =>
                    $this->message->conversation_id,

                'sender_id' =>
                    $this->message->sender_id,

                'message_content' =>
                    $this->message->message_content,

                'is_unsent' =>
                    (bool) $this->message->is_unsent,

                'unsent_at' =>
                    $this->message->unsent_at?->toISOString(),

                'is_edited' =>
                    (bool) $this->message->is_edited,

                'edited_at' =>
                    $this->message->edited_at?->toISOString(),

                'updated_at' =>
                    $this->message->updated_at?->toISOString(),
            ],
        ];
    }
}