<?php

namespace App\Listeners;

use App\Events\RetrievedData;
use App\Events\UserRetrievedData;
use OwenIt\Auditing\Models\Audit;

class AuditDataRetrieval
{
    public function handle(RetrievedData $event): void
    {
        $user = $event->user;
        $auditable = $event->auditable;
        $request = $event->request;

        $auditableType = null;
        $auditableIds = null;
        $retrievedSize = null;

        if (is_a($auditable, 'Illuminate\Database\Eloquent\Collection')) {
            $auditableType = get_class($auditable[0]);
            $auditableIds = $auditable->pluck('id');
            $retrievedSize = $auditable->count();
        } else {
            $auditableType = get_class($auditable);
            $auditableIds = $auditable->id;
            $retrievedSize = 1;
        }

        Audit::create([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'event' => 'retrieved',
            'auditable_type' => $auditableType,
            'retrieved_auditable_ids' => $auditableIds,
            'retrieved_size' =>  $retrievedSize,
            'url' => $request->url(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
