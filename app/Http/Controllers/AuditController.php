<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
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

        $audits = $this->getAudits($request);

        $page = $request->input('page',1);
        $data = array_slice($audits, ($page - 1) * 15, 15);
        $paginator = new LengthAwarePaginator(
            $data, 
            count($audits), 
            15, 
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $paginator->appends($request->query());

        return $paginator;
    }

    public function downloadAudits(Request $request)
    {
        $request->validate([
            'page' => 'integer',
            'resource' => 'string|in:user,patient,consultation record,payment',
            'date_from' => 'date',
            'date_until' => 'date|after_or_equal:date_from'
        ]);

        $audits = $this->getAudits($request);

        $pdf = Pdf::loadView('audits', ['audits' => $audits])->setPaper('a4', 'landscape');

        return $pdf->download('audits.pdf');
    }

    public function getAudits(Request $request)
    {
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

        $auditsQuery = $auditsQuery->orderBy('created_at', 'desc')->get();

        $audits = [];

        foreach ($auditsQuery as $audit)
        {
            $user = $request->user();
            $resourceInformation = $this->getResourceInformation($audit);

            if ($audit->event !== 'updated')
            {
                $audit->old_values = null;
                $audit->new_values = null;
            }

            $audits[] = [
                'id' => $audit->id,
                'date' => $audit->created_at->format('Y-m-d'),
                'time' => $audit->created_at->format('H:m A'),
                'user_role' => $user ? ucfirst($user->role) : null,
                'user_personnel_number' => $user ?-> personnel_number,
                'user_name' => $user ?-> full_name,
                'action' => ucfirst($audit->event),
                'message' => ucfirst($this->getAuditMessage($audit)),
                'resource_type' => $resourceInformation['resource_type'],
                'resource_id' => $resourceInformation['resource_id'],
                'resource_entity' => $resourceInformation['resource_entity'],
                'old_values' => $this->formatArrayToString($audit->old_values),
                'new_values' => $this->formatArrayToString($audit->new_values)
            ];
        } 

        return $audits;
    }

    public function formatArrayToString($array)
    {
        $formatted = [];

        if (is_array($array))
        {
            foreach ($array as $key => $value)
            {
                $key = ucwords(str_replace('_', " ", $key));
                $formatted[] = "{$key}: $value";
            }
        }
    
        return $formatted;
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

    public function getResourceType(string $auditableType)
    {
        return ucfirst(preg_replace('/(?<!^)(?=[A-Z])/', ' ', class_basename($auditableType)));
    }

    public function getResourceInformation(Audit $audit): array
    {
        $auditable = $audit->auditable;

        $resourceType = $audit->auditable_type ? strtolower($this->getResourceType($audit->auditable_type)) : null;
        $resourceId = $auditable ?-> id;
        $resourceEntity = null;

        if ($auditable)
        {
            if (in_array($resourceType, ['user', 'patient']))
            {
                $resourceEntity = $resourceType === 'user' ? $auditable->personnel_number : $auditable->patient_number;
            }
            else if (in_array($resourceType, ['payment', 'consultationrecord']))
            {
                $resourceEntity = $auditable->patient->patient_id;
            }
        }

        return ['resource_type' => ucfirst($resourceType), 'resource_id' => $resourceId, 'resource_entity' => $resourceEntity];
    }

    public function getAuditMessage(Audit $audit)
    {
        $auditMessage = null;

        $resource = $audit->auditable_type ? $this->getResourceType($audit->auditable_type) : null;
        $event = $audit->event;

        if (in_array($event, ['created', 'updated'])) 
        {
            $auditMessage = "{$event} a {$resource}";
        } 
        else if ($event == 'retrieved') 
        {
            $auditMessage = "viewed a {$resource}";
        } 
        else if (in_array($event, ['unrestricted', 'locked', 'unlocked'])) 
        {
            $auditMessage = "{$event} a user";
        }
        else if ($event == 'sent reset link') 
        {
            $auditMessage = "sent reset link to user";
        } 
        else if (in_array($event, ['login', 'logout', 'reset password', 'restricted'])) 
        {
            $auditMessage = $event;
        }

        return strtolower($auditMessage);
    }
}
