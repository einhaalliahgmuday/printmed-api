<?php

namespace App\Listeners;

use App\AccountActionEnum;
use App\Events\AccountAction;
use OwenIt\Auditing\Models\Audit;

class AuditAccountAction
{
    public function handle(AccountAction $event): void
    {
        $action = $event->action;
        $user = $event->user;
        $auditable = $event->auditable;
        $request = $event->request;

        $auditEvent = null;

        if ($action === AccountActionEnum::LOCK) 
        {
            $auditEvent = $user->is_locked ? 'locked' : 'unlocked';
        } else if ($action === AccountActionEnum::RESTRICT) 
        {
            $auditEvent = $user->failed_login_attempts >= 3 ? 'restricted' : 'unrestricted';
        } else if ($action === AccountActionEnum::LOGIN) 
        {
            $auditEvent = 'login';
        } else if ($action === AccountActionEnum::LOGOUT) 
        {
            $auditEvent = 'logout';
        } else if ($action === AccountActionEnum::SENT_RESET_LINK) 
        {
            $auditEvent = 'sent reset link';
        } else if ($action === AccountActionEnum::RESET_PASSWORD) 
        {
            $auditEvent = 'reset password';
        }

        Audit::create([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'event' => $auditEvent,
            'auditable_id' => $auditable ?-> id,
            'auditable_type' => $auditable !== null ? get_class($auditable) : null,
            'url' => $request->url(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
