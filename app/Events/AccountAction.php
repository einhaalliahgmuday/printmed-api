<?php

namespace App\Events;

use App\AccountActionEnum;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class AccountAction
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AccountActionEnum $action;
    public User $user;
    public User|null $auditable;
    public Request $request;

    public function __construct(AccountActionEnum $action, User $user, User|null $auditable, Request $request)
    {
        $this->action = $action;
        $this->user = $user;
        $this->auditable = $auditable;
        $this->request = $request;
    }
}
