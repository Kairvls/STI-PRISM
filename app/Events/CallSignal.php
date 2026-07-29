<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $targetUserId,
        public int $fromUserId,
        public string $fromUserName,
        public ?string $fromUserPicture,
        public ?int $conversationId,
        public string $callId,
        public string $signalType,
        public string $callType = 'audio',
        public array $payload = [],
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->targetUserId}")];
    }

    public function broadcastAs(): string
    {
        return 'call.signal';
    }

    public function broadcastWith(): array
    {
        return [
            'target_user_id' => $this->targetUserId,
            'from_user_id' => $this->fromUserId,
            'from_user_name' => $this->fromUserName,
            'from_user_picture' => $this->fromUserPicture,
            'conversation_id' => $this->conversationId,
            'call_id' => $this->callId,
            'signal_type' => $this->signalType,
            'call_type' => $this->callType,
            'payload' => $this->payload,
        ];
    }
}
