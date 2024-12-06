<?php 

namespace App\Traits;

use App\Models\Audit;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;

trait AuditTrait
{
    public function formatAudit(Audit $audit) {
        $user = $audit->user;
        $resourceType = $audit->auditable_type ? strtolower($this->getResourceType($audit->auditable_type)) : null;
        $resourceId = $audit->auditable_id ?? null;
        $resourceEntity = "";

        if ($resourceType && $resourceId) {
            $resourceEntity = $this->getResourceEntity($resourceType, $resourceId);
        }

        if ($audit->event !== 'updated') {
            $audit->old_values = null;
            $audit->new_values = null;
        }

        $formattedAudit = [
            'id' => $audit->id,
            'date' => $audit->created_at->format('Y-m-d'),
            'time' => $audit->created_at->format('h:i A'),
            'user_role' => $user ? ucfirst($user->role) : null,
            'user_personnel_number' => $user ?-> personnel_number,
            'user_name' => $user ?-> full_name,
            'action' => ucfirst($audit->event),
            'message' => ucfirst($this->getAuditMessage($audit)),
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'resource_entity' => $resourceEntity,
            'old_values' => $audit->old_values == null ? null : $this->formatValues($audit->old_values),
            'new_values' => $audit->new_values == null ? null : $this->formatValues($audit->new_values)
        ];

        return $formattedAudit;
    }

    public function getResourceEntity(string $auditableType, int $auditableId): string
    {
        switch($auditableType) {
            case "user": 
                $user = User::select('personnel_number')->find($auditableId);
                return $user->personnel_number;
            case "patient":
                $patient = Patient::select('patient_number')->find($auditableId);
                return $patient->patient_number;
            case "consultation":
                $patient = Consultation::find($auditableId)->patient()->select('patient_number')->first();
                return $patient->patient_number;
            default:
                return "";
        }
    }

    public function getResourceType(string $auditableType)
    {
        return ucfirst(preg_replace('/(?<!^)(?=[A-Z])/', ' ', class_basename($auditableType)));
    }

    public function getAuditMessage(Audit $audit)
    {
        $auditMessage = null;

        $resource = $audit->auditable_type ? $this->getResourceType($audit->auditable_type) : null;
        $event = $audit->event;

        if ($event == 'updated') {
            $auditMessage = "{$event} a {$resource}";
        } else if ($event == 'created') {
            $auditMessage = "added a {$resource}";
        } else if ($event == 'retrieved') {
            $auditMessage = "viewed a {$resource}";
        } else if (in_array($event, ['locked', 'unlocked'])) {  // 'unrestricted'
            $auditMessage = "{$event} a user";
        } else if (in_array($event, ['login', 'logout'])) { // 'reset password', 'restricted'
            $auditMessage = $event;
        }
        // else if ($event == 'sent reset link') 
        // {
        //     $auditMessage = "sent reset link to user";
        // } 

        return strtolower($auditMessage);
    }

    public function formatValues(array $array)
    {
        $formatted = [];

        if (is_array($array))
        {
            foreach ($array as $key => $value)
            {
                $key = ucwords(str_replace('_', " ", $key));
                $formatted[] = "{$key}: {$value}";
            }
        }
    
        return $formatted;
    }
}