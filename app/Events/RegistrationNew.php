<?php

namespace App\Events;

use App\Models\Registration;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RegistrationNew implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $registration;

    public function __construct(Registration $registration)
    {
        $this->registration = $registration;
    }


    public function broadcastOn(): Channel
    {
        return new PrivateChannel('registration');
    }
}
