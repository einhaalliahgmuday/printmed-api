<?php

namespace App\Listeners;

use App\Events\UpdateUser;
use OwenIt\Auditing\Models\Audit;

class AuditUserUpdate
{
    public function handle(UpdateUser $event): void
    {
        $user = $event->user;
        $originalData = $event->originalData;
        $updatedData = $event->updatedData;
        $request = $event->request;

        $oldValues = [];
        $newValues = [];

        foreach ($updatedData->toArray() as $key => $updatedValue) {
            if ($key == 'updated_at') {
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
