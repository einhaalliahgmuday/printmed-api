<?php

namespace App\Events;

use App\Models\Audit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditNew implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $audit;

    public function __construct(Audit $audit)
    {
        $this->audit = $audit;
    }


    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('audit-channel'),
        ];
    }

    public function broadcastAs()
    {
        return "audit-new";
    }
}