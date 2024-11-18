<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Audit;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class AuditController extends Controller
{
    public function index(Request $request) 
    {
        $request->validate([
            'page' => 'integer',
            'resource' => 'string|in:user,patient,consultation,payment',
            'date_from' => 'date|date_format:Y-m-d',
            'date_until' => 'date|date_format:Y-m-d|after_or_equal:date_from'
        ]);

        $page = $request->input('page',1);
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $auditsInformation = $this->getAudits($request, $offset, $limit);

        $paginator = new LengthAwarePaginator(
            $auditsInformation['data'], 
            $auditsInformation['totalQuery'], 
            $limit, 
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $paginator->appends($request->query());

        return $paginator;
    }

    public function downloadAudits(Request $request)
    {
        $request->validate([
            'resource' => 'string|in:user,patient,consultation record,payment',
            'date_from' => 'date|date_format:Y-m-d',
            'date_until' => 'date|date_format:Y-m-d|after_or_equal:date_from'
        ]);

        $auditsInformation = $this->getAudits($request, null, null);

        $date = now()->format('Y-m-d');

        $pdf = SnappyPdf::loadView('audits', ['audits' => $auditsInformation['data']])->setPaper('a4', 'landscape');

        return $pdf->download("printmed_audits_retrieved_at_{$date}.pdf");
    }

    public function getAudits(Request $request, int|null $offset, int|null $limit)
    {
        $auditsQuery = Audit::query();

        if ($request->filled('resource'))
        {
            $auditsQuery = $auditsQuery->whereBlind('auditable_type', 'auditable_type_index', $this->getRequestResourceClass($request->resource));
        }

        $dateFrom = Carbon::parse($request->date_until)->startOfDay();
        if ($request->filled('date_from'))
        {
            $auditsQuery = $auditsQuery->where('created_at', '>=', $dateFrom);
        }

        $dateUntil = Carbon::parse($request->date_until)->endOfDay();
        if ($request->filled('date_until'))
        {
            $auditsQuery = $auditsQuery->where('created_at', '<=', $dateUntil);
        }
        $auditsQuery->orderBy('created_at', 'desc');

        $totalQuery = 0;

        if ($offset !== null && $limit !== null)
        {
            $auditsQueryClone = clone $auditsQuery;
            $totalQuery = $auditsQueryClone->count();

            $auditsQuery = $auditsQuery->offset($offset)->limit($limit)->get();
        }
        else 
        {
            $auditsQuery = $auditsQuery->get();
        }

        $audits = [];

        foreach ($auditsQuery as $audit)
        {
            $user = $audit->user;
            $resourceInformation = $this->getResourceInformation($audit);

            if ($audit->event !== 'updated')
            {
                $audit->old_values = null;
                $audit->new_values = null;
            }

            $audits[] = [
                'id' => $audit->id,
                'date' => $audit->created_at->format('Y-m-d'),
                'time' => $audit->created_at->format('h:i A'),
                'user_role' => $user ? ucfirst($user->role) : null,
                'user_personnel_number' => $user ?-> personnel_number,
                'user_name' => $user ?-> full_name,
                'action' => ucfirst($audit->event),
                'message' => ucfirst($this->getAuditMessage($audit)),
                'resource_type' => $resourceInformation['resource_type'],
                'resource_id' => $resourceInformation['resource_id'],
                'resource_entity' => $resourceInformation['resource_entity'],
                'old_values' => $audit->old_values == null ? null : $this->formatValues($audit->old_values),
                'new_values' => $audit->new_values == null ? null : $this->formatValues($audit->new_values)
            ];
        } 

        $auditsInformation = [
            'data' => $audits,
            'totalQuery' => $totalQuery
        ];

        return $auditsInformation;
    }

    public function formatValues(array $array)
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
            case 'consultation':
                $requestResourceClass = 'App\Models\Consultation';
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
            else if (in_array($resourceType, ['payment', 'consultation']))
            {
                $resourceEntity = $auditable->patient->patient_number;
            }
        }

        return ['resource_type' => ucfirst($resourceType), 'resource_id' => $resourceId, 'resource_entity' => $resourceEntity];
    }

    public function getAuditMessage(Audit $audit)
    {
        $auditMessage = null;

        $resource = $audit->auditable_type ? $this->getResourceType($audit->auditable_type) : null;
        $event = $audit->event;

        if ($event == 'updated') 
        {
            $auditMessage = "{$event} a {$resource}";
        }
        else if ($event == 'created') 
        {
            $auditMessage = "added a {$resource}";
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
