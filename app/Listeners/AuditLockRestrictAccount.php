<?php

namespace App\Listeners;

use App\Events\LockRestrictAccount;
use App\LockRestrictAction;
use OwenIt\Auditing\Models\Audit;

class AuditLockRestrictAccount
{
    public function handle(LockRestrictAccount $event): void
    {
        $action = $event->action;   //LockRestrictAction enum
        $user = $event->user;
        $auditable = $event->auditable;
        $request = $event->request;

        $auditEvent = null;

        if ($action === LockRestrictAction::LOCK) 
        {
            $auditEvent = $auditable->is_locked ? 'locked' : 'unlocked';
        } else if ($action === LockRestrictAction::RESTRICT) 
        {
            $auditEvent = $auditable->failed_login_attempts >= 3 ? 'restricted' : 'unrestricted';
        }

        Audit::create([
            'user_type' => $user === null ? null: get_class($user),
            'user_id' => $user === null ? null : $user->id,
            'event' => $auditEvent,
            'auditable_id' => $auditable->id,
            'auditable_type' => get_class($auditable),
            'url' => $request->url(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
