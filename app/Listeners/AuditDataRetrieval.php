<?php

namespace App\Listeners;

use App\Events\RetrievedData;
use OwenIt\Auditing\Models\Audit;

class AuditDataRetrieval
{
    public function handle(RetrievedData $event): void
    {
        $user = $event->user;
        $auditable = $event->auditable;
        $request = $event->request;

        Audit::create([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'event' => 'retrieved',
            'auditable_id' => $auditable->id,
            'auditable_type' => get_class($auditable),
            'url' => $request->url(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
