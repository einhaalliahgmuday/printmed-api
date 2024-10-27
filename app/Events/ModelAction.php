<?php

namespace App\Events;

use App\AuditAction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class ModelAction
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AuditAction $action;
    public User $user;
    public Model|null $auditable;
    public array|null $originalData;
    public Request $request;

    public function __construct(AuditAction $action, User $user, Model|null $auditable, array|null $originalData, Request $request)
    {
        $this->action = $action;
        $this->user = $user;
        $this->auditable = $auditable;
        $this->originalData = $originalData;
        $this->request = $request;
    }
}
