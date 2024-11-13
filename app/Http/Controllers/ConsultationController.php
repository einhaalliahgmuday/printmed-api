<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use App\Events\PaymentNew;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConsultationController extends Controller
{
    //store and update has policy implemented
    //only physicians of the patient can create a record
    //only the physician of a record can update the record

    // cannot get records if not a patient of user
    public function index(Request $request, Patient $patient) {
        // Gate::authorize('view', [$request->user(), $patient]);

        return Consultation::where('patient_id', $patient->id)
                            ->orderBy('created_at')
                            ->select('id', 'chief_complaint', 'primary_diagnosis')
                            ->paginate(10);
    }
    
    // cannot add records if not a patient of user
    public function store(Request $request)
    {
        $fields = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'height' => 'numeric|decimal:0,2',
            'weight' => 'numeric|decimal:0,2',
            'blood_pressure' => 'string|max:7',
            'temperature' => 'numeric|decimal:0,2',
            'chief_complaint' => 'required|string',
            'history_of_present_illness' => 'string',
            'family_hx' => 'string',
            'medical_hx' => 'string',
            'pediatrics_h' => 'string',
            'pediatrics_e' => 'string',
            'pediatrics_a' => 'string',
            'pediatrics_d' => 'string',
            'primary_diagnosis' => 'string',
            'diagnosis' => 'required|string',
            'prescription' => 'required|string',
            'follow_up_date' => 'date|date_format:Y-m-d',
            'payment_amount' => 'required|integer',
            'payment_method' => 'required|string|in:cash,hmo',
            'payment_hmo' => 'string'
        ]);

        Gate::authorize('create', [Consultation::class, $request]);

        $user = $request->user();

        $fields['physician_id'] = $user->id;
        $fields['department_id'] = $user->department_id;

        $consultationRecord = Consultation::create($fields);

        $paymentFields = [
            'amount' => $request->payment_amount,
            'method' => $request->payment_method,
            'hmo' => $request->payment_hmo ?: null,
            'consultation_record_id' => $consultationRecord->id,
            'patient_id' => $request->patient_id,
            'physician_id' => $fields['physician_id'],
            'department_id' => $fields['department_id'],
        ];

        $payment = Payment::create($paymentFields);

        // web socket for new payment
        event(new PaymentNew($payment));

        // audit creation of consultation record and payment record
        event(new ModelAction(AuditAction::CREATE, $request->user(), $consultationRecord, null, $request));
        event(new ModelAction(AuditAction::CREATE, $request->user(), $payment, null, $request));
    
        return response()->json([
            'consultation_record' => $consultationRecord,
            'payment' => $payment
        ]);
    }

    public function show(Request $request, Consultation $consultation)
    {
        Gate::authorize('view', $consultation);

        $payment = $consultation->payment()->first();

        // implements audit of retrieval
        event(new ModelAction(AuditAction::RETRIEVE, $request->user(), $consultation, null, $request));

        return response()->json([
            'consultation' => $consultation,
            'payment' => $payment
        ]);
    }

    public function update(Request $request, Consultation $consultation)
    {
        Gate::authorize('update', $consultation);

        $fields = $request->validate([
            'height' => 'numeric|decimal:0,2',
            'weight' => 'numeric|decimal:0,2',
            'blood_pressure' => 'string|max:7',
            'temperature' => 'numeric|decimal:0,2',
            'chief_complaint' => 'string',
            'history_of_present_illness' => 'string',
            'family_hx' => 'string',
            'medical_hx' => 'string',
            'pediatrics_h' => 'string',
            'pediatrics_e' => 'string',
            'pediatrics_a' => 'string',
            'pediatrics_d' => 'string',
            'primary_diagnosis' => 'string',
            'diagnosis' => 'string',
            'prescription' => 'string',
            'follow_up_date' => 'date|date_format:Y-m-d',
        ]);

        $originalData = $consultation->toArray();

        $consultation->update($fields);

        event(new ModelAction(AuditAction::UPDATE, $request->user(), $consultation, $originalData, $request));

        return $consultation;
    }

    public function destroy(Consultation $consultation)
    {
        $dateThreshold = now()->subYears(10);

        //if patient or patient's last consultation date is not past 10 years, patient cannot be deleted
        if ($consultation->updated_at >= $dateThreshold) 
        {
            return response()->json([
                'message' => 'Consultation record cannot be deleted 10 years before.'
            ], 403);
        }

        $consultation->delete();

        return response()->json([
            'message' => 'Consultation record successfully deleted.'
        ]);
    }
}
