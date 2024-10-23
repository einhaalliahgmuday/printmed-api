<?php

namespace App\Events;

use App\LockRestrictAction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class LockRestrictAccount
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public LockRestrictAction $action;
    public User|null $user;
    public User $auditable;
    public Request $request;

    public function __construct(LockRestrictAction $action, User|null $user, User $auditable, Request $request)
    {
        $this->action = $action;
        $this->user = $user;
        $this->auditable = $auditable;
        $this->request = $request;
    }

}
