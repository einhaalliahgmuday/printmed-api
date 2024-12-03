<?php

namespace App\Events;

use App\Models\Audit;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditNew implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $audit;

    public function __construct(array $audit)
    {
        $this->audit = $audit;
    }


    public function broadcastOn(): Channel
    {
        return new PrivateChannel('audit');
    }
}