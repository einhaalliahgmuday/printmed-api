<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    public function index(Request $request) 
    {
        $request->validate([
            'page' => 'integer',
            'resource' => 'string|in:user,patient,consultation record,payment',
            'date_from' => 'date',
            'date_until' => 'date|after_or_equal:date_from'
        ]);

        $audits = [];

        $auditsQuery = Audit::query();

        if ($request->filled('resource'))
        {
            $auditsQuery = $auditsQuery->where('auditable_type', $this->getRequestResourceClass($request->resource));
        }

        if ($request->filled('date_from'))
        {
            $auditsQuery = $auditsQuery->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_until'))
        {
            $auditsQuery = $auditsQuery->where('created_at', '>=', $request->date_until);
        }

        foreach ($auditsQuery->orderBy('created_at', 'desc')->get() as $audit)
        {
            $user = $audit->user;
            
            $resource = $audit->auditable_type ? $this->getResource($audit->auditable_type) : null;

            $audits[] = [
                'id' => $audit->id,
                'date' => $audit->created_at->format('Y-m-d'),
                'time' => $audit->created_at->format('H:m A'),
                'user_role' => $user ? ucfirst($user->role) : null,
                'user_personnel_number' => $user ?-> personnel_number,
                'user_name' => $user ?-> full_name,
                'action' => strtoupper($audit->event),
                'resource' => $resource,
                'old_values' => $audit ?-> old_values,
                'new_values' => $audit ?-> new_values,
                'size' => $audit->retrieved_size,
                'message' => $this->getAuditMessage($audit)
            ];
        }

        $page = $request->input('page',1);
        $data = array_slice($audits, ($page - 1) * 15, 15);
        $paginator = new LengthAwarePaginator(
            $data, 
            count($audits), 
            15, 
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $paginator;
    }

    public function show(Audit $audit)
    {
        
    }

    public function getResource(string $auditableType)
    {
        return preg_replace('/(?<!^)(?=[A-Z])/', ' ', class_basename($auditableType));
    }

    public function getRequestResourceClass(string $resource)
    {
        $requestResourceClass = null;

        switch ($resource) {
            case 'user':
                $requestResourceClass = 'App\Models\User';
                break;
            case 'patient':
                $requestResourceClass = 'App\Models\Patient';
                break;
            case 'consultation record':
                $requestResourceClass = 'App\Models\ConsultationRecord';
                break;
            case 'payment':
                $requestResourceClass = 'App\Models\Payment';
                break;
        }

        return $requestResourceClass;
    }

    public function getAuditMessage(Audit $audit)
    {
        $auditMessage = null;

        $resource = $audit->auditable_type ? $this->getResource($audit->auditable_type) : null;
        $event = $audit->event;

        if (in_array($event, ['created', 'updated'])) {
            $auditMessage = "{$event} A {$resource}";
        } else if ($event == 'retrieved') {
            $retrieved_size = $audit->retrieved_size;
            $auditMessage = "RETRIEVED {$retrieved_size} {$resource}";
            if ($retrieved_size > 1) { $auditMessage .= "S"; }
        } else if (in_array($event, ['unrestricted', 'locked', 'unlocked'])) {
            $auditMessage = "{$event} USER {$audit->auditable->personnel_number}";
        } else if ($event == 'restricted') {
            $auditMessage = "RESTRICTED: 3 FAILED LOGINS";
        } else if ($event == 'sent reset link') {
            $auditMessage = "SENT RESET LINK TO USER {$audit->auditable->personnel_number}";
        } else if (in_array($event, ['login', 'logout', 'reset password'])) {
            $auditMessage = $event;
        }

        return strtoupper($auditMessage);
    }
}
