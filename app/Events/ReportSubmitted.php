<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReportSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $report;

    public function __construct($report)
    {
        $this->report = $report;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('reports'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ReportSubmitted';
    }

    public function broadcastWith(): array
    {
        Log::info('BroadcastWith executed');
        return [
            'report' => $this->report,
        ];
    }
}