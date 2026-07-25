<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    // =====================================================
    // MESSAGE
    // =====================================================

    public Message $message;


    // =====================================================
    // CREATE EVENT
    // =====================================================

    public function __construct(Message $message)
    {
        $this->message = $message;
    }


    // =====================================================
    // PRIVATE CONVERSATION CHANNEL
    // =====================================================

    // =====================================================
    // BROADCAST CHANNELS
    //
    // 1. conversation.{id}
    //    Updates the currently opened chat.
    //
    // 2. user.{id}
    //    Updates conversation lists for every receiver.
    // =====================================================

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel(
                'conversation.' . $this->message->conversation_id
            ),
        ];


        // =================================================
        // GET ALL PARTICIPANTS EXCEPT SENDER
        // =================================================

        $receiverIds = DB::table('conversation_participants')

            ->where(
                'conversation_id',
                $this->message->conversation_id
            )

            ->where(
                'user_id',
                '!=',
                $this->message->sender_id
            )

            ->pluck('user_id');


        // =================================================
        // BROADCAST TO EACH RECEIVER
        // =================================================

        foreach ($receiverIds as $receiverId) {

            $channels[] = new PrivateChannel(
                'user.' . $receiverId
            );

        }


        return $channels;
    }


    // =====================================================
    // EVENT NAME RECEIVED BY JAVASCRIPT
    // =====================================================

    public function broadcastAs(): string
    {
        return 'message.sent';
    }


    // =====================================================
    // DATA SENT TO THE RECEIVER
    // =====================================================

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'message_id'
                    => $this->message->message_id,

                'conversation_id'
                    => $this->message->conversation_id,

                'sender_id'
                    => $this->message->sender_id,

                'message_content'
                    => $this->message->message_content,

                'created_at'
                    => $this->message->created_at,

                'sender' => [
                    'user_id'
                        => $this->message->sender?->user_id,

                    'user_full_name'
                        => $this->message->sender?->user_full_name,
                ],
            ],
        ];
    }
}