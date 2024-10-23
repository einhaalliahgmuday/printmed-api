<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use PhpParser\Node\Expr\ArrayItem;

class UpdateUser
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public array $originalData;
    public User $updatedData;
    public Request $request;

    public function __construct(User $user, array $originalData, User $updatedData, Request $request)
    {
        $this->user = $user;
        $this->originalData = $originalData;
        $this->updatedData = $updatedData;
        $this->request = $request;
    }
}
