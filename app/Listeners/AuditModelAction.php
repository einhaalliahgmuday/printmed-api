<?php

namespace App\Listeners;

use App\AuditAction;
use App\Events\ModelAction;
use App\Models\Audit;

class AuditModelAction
{
    public function handle(ModelAction $event): void
    {
        $action = $event->action;
        $user = $event->user;
        $auditable = $event->auditable;
        $originalData = $event->originalData;
        $request = $event->request;

        $auditEvent = $action->value;

        if ($action === AuditAction::LOCK) {
            $auditEvent = $user->is_locked ? 'locked' : 'unlocked';
        }

        $oldValues = null;
        $newValues = null;

        if ($action === AuditAction::CREATE) 
        {
            $newValues = $auditable->toArray();
            unset($newValues['updated_at']);
            unset($newValues['created_at']);
        }
        else if ($action === AuditAction::UPDATE)
        {
            foreach ($auditable->toArray() as $key => $updatedValue) {
                if ($key == 'updated_at' || $key == 'created_at') {
                    continue;
                } 
                else if (array_key_exists($key, $originalData)) {
                    $originalValue = $originalData[$key];
                    if ($originalValue !== $updatedValue) {
                        $oldValues[$key] = $originalValue;
                        $newValues[$key] = $updatedValue;
                    }
                } 
                else {
                    $newValues[$key] = $updatedValue;
                }
            }
        }

        if ($action === AuditAction::UPDATE && ($newValues === null || count($newValues) < 1))
        {
            return;
        }

        Audit::create([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'event' => $auditEvent,
            'auditable_id' => $auditable ?-> id,
            'auditable_type' => $auditable !== null ? get_class($auditable) : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $request->url(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
