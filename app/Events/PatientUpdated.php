<?php

namespace App\Events;

use App\Models\Patient;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $patient;

    public function __construct(Patient $patient)
    {
        $this->patient = $patient;
    }


    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('patient-channel'),
        ];
    }

    public function broadcastAs()
    {
        return "patient-updated";
    }
}