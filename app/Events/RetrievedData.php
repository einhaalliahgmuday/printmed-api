<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class RetrievedData
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Model|Collection $auditable;
    public Request $request;

    public function __construct(User $user, Model|Collection $auditable, Request $request)
    {
        $this->user = $user;
        $this->auditable = $auditable;
        $this->request = $request;
    }
}
