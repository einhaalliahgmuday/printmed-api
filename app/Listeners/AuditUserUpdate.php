<?php

namespace App\Listeners;

use App\Events\UpdateUser;
use OwenIt\Auditing\Models\Audit;

class AuditUserUpdate
{
    public function handle(UpdateUser $event): void
    {
        $user = $event->user;
        $originalData = $event->originalData;   //array
        $updatedData = $event->updatedData;     //User model
        $request = $event->request;

        $newValues = $updatedData->getChanges();
        unset($newValues['updated_at']);

        $oldValues = [];
        foreach (array_keys($newValues) as $key)
        {
            if (array_key_exists($key, $originalData))
            {
                $oldValues[$key] = $originalData[$key];
            }
        }

        Audit::create([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'event' => 'updated',
            'auditable_id' => $updatedData->id,
            'auditable_type' => get_class($updatedData),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $request->url(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
