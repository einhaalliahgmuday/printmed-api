<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }


    public function broadcastOn(): array
    {
        return [
            new Channel('payment-channel'),
        ];
    }

    public function broadcastAs()
    {
        return "payment-updated-department-{$this->payment->department_id}"; // ex. payment-updated-department-1 (means a payment record was updated for department id 1)
    }
}
