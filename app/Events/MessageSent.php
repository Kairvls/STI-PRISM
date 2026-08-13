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
        $this->message->loadMissing([
            'sender',
            'call',
        ]);

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

                'message_type'
                    => $this->message->message_type,

                'call_id'
                    => $this->message->call_id,

                'created_at'
                    => $this->message->created_at,

                'sender' => [
                    'user_id'
                        => $this->message->sender?->user_id,

                    'user_full_name'
                        => $this->message->sender?->user_full_name,
                ],

                'call' => $this->message->call
                    ? [
                        'call_id' => $this->message->call->call_id,
                        'caller_id' => $this->message->call->caller_id,
                        'receiver_id' => $this->message->call->receiver_id,
                        'call_type' => $this->message->call->call_type,
                        'status' => $this->message->call->status,
                        'duration' => $this->message->call->duration,
                        'answered_at' => $this->message->call->answered_at,
                    ]
                    : null,

                'reply_to_message_id'
                    => $this->message->reply_to_message_id,

                'reply_to'
                    => $this->message->replyTo
                        ? [
                            'message_id'
                                => $this->message->replyTo->message_id,

                            'message_content'
                                => $this->message->replyTo->message_content,

                            'sender' => [
                                'user_id'
                                    => $this->message->replyTo->sender?->user_id,

                                'user_full_name'
                                    => $this->message->replyTo->sender?->user_full_name,
                            ],
                        ]
                        : null,
            ],
        ];
    }
}