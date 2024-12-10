<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VitalSignsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $vitalSigns;

    public function __construct($vitalSigns)
    {
        $this->vitalSigns = $vitalSigns;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('vital-signs.' . $this->vitalSigns->patient_id);
    }
}
