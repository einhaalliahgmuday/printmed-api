<?php

namespace App\Listeners;

use App\Events\UserRetrievedData;
use OwenIt\Auditing\Models\Audit;

class AuditUserRetrievedData
{
    public function handle(UserRetrievedData $event): void
    {
        $user = $event->user;
        $data = $event->data;
        $request = $event->request;

        $auditableType = null;
        $auditableIds = null;
        $retrievedSize = null;

        if (is_a($data, 'Illuminate\Database\Eloquent\Collection')) {
            $auditableType = get_class($data[0]);
            $auditableIds = $data->pluck('id');
            $retrievedSize = $data->count();
        } else {
            $auditableType = get_class($data);
            $auditableIds = $data->id;
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

        // $data = count($data);

        // Audit::create([
        //     'user_type' => get_class($user),
        //     'user_id' => $user->id,
        //     'event' => 'retrieved',
        //     'auditable_type' => get_class($data),
        //     'auditable_id' => $data->id,
        //     'url' => $request->url(),
        //     'ip_address' => $request->ip(),
        //     'user_agent' => $request->userAgent(),
        // ]);
    }
}
