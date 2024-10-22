<?php

namespace App\Events;

use App\Models\Queue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('queue-channel')
        ];
    }

    public function broadcastAs()
    {
        return "queue-{$this->queue->id}-updated";   // ex. queue-1-updated (means queue of id 1 is updated)
    }
}
