<?php

namespace App\Listeners;

use App\AuditAction;
use App\Events\AuditNew;
use App\Events\ModelAction;
use App\Models\Audit;
use App\Traits\AuditTrait;

class AuditModelAction
{
    use AuditTrait;

    public function handle(ModelAction $event): void
    {
        $action = $event->action;
        $user = $event->user;
        $auditable = $event->auditable;
        $originalData = $event->originalData;
        $request = $event->request;

        $auditEvent = $action->value;

        if ($action === AuditAction::LOCK) {
            $auditEvent = $auditable->is_locked == true ? 'locked' : 'unlocked';
        }

        $oldValues = null;
        $newValues = null;

        if ($action === AuditAction::CREATE) 
        {
            $newValues = $auditable->toArray();
            unset($newValues['updated_at']);
            unset($newValues['created_at']);

            if (get_class($auditable) === "App\Models\Consultation") 
            {
                $prescriptions = $newValues['prescriptions']->toArray();

                if(count($prescriptions) > 0) {
                    $newValues['prescriptions'] = $this->formatPrescriptions($prescriptions);
                }
                else {
                    unset($newValues['prescriptions']);
                }
            }
        }
        else if ($action === AuditAction::UPDATE)
        {
            if (get_class($auditable) === "App\Models\Consultation") 
            {
                $oldPrescriptions = $originalData['prescriptions'];
                $newPrescriptions = $auditable->prescriptions->toArray();

                $new = [];
                $old = [];

                foreach ($newPrescriptions as $prescription) {
                    $new[] = $this->formatSinglePrescription($prescription);
                }

                foreach ($oldPrescriptions as $prescription) {
                    $old[] = $this->formatSinglePrescription($prescription);
                }

                $newDiff = array_diff($new, $old);
                $oldDiff = array_diff($old, $new);

                $newFormatted = implode('; ', $newDiff);
                $oldFormatted = implode('; ', $oldDiff);

                if ($newFormatted || $oldFormatted) {
                    $oldValues['prescriptions'] = $oldFormatted;
                    $newValues['prescriptions'] = $newFormatted;
                }
                
                unset($auditable->prescriptions);
                unset($originalData['prescriptions']);
            }

            foreach ($auditable->toArray() as $key => $updatedValue) {
                if ($key == 'updated_at' || $key == 'created_at' || $key == 'prescriptions') {
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

        $audit = Audit::create([
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

        $formattedAudit = $this->formatAudit($audit);
        AuditNew::dispatch($formattedAudit);
    }

    protected function formatPrescriptions(array $prescriptions)
    {
        $replacement = "";

        if(count($prescriptions) > 0) {
            foreach ($prescriptions as $prescription) 
            {
                $replacement .= "{$prescription['name']} {$prescription['dosage']} {$prescription['instruction']}; ";
            }
        }

        return $replacement;
    }

    protected function formatSinglePrescription(array $prescription)
    {
        $formatted = "";

        if(count($prescription) > 0) {
            $formatted .= "{$prescription['name']} {$prescription['dosage']} {$prescription['instruction']}";
        }

        return $formatted;
    }
}
