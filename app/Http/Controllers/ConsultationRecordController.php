<?php

namespace App\Http\Controllers;

use App\Events\PaymentNew;
use App\Events\RetrievedData;
use App\Models\ConsultationRecord;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConsultationRecordController extends Controller
{
    //store and update has policy implemented
    //only physicians of the patient can create a record
    //only the physician of a record can update the record
    
    public function store(Request $request)
    {
        $fields = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'height' => 'decimal:0,2',
            'weight' => 'decimal:0,2',
            'blood_pressure' => 'string|max:7',
            'temperature' => 'decimal:0,2',
            'chief_complaint' => 'required',
            'history_of_present_illness' => 'string',
            'family_hx' => 'string',
            'medical_hx' => 'string',
            'pediatrics_h' => 'string',
            'pediatrics_e' => 'string',
            'pediatrics_a' => 'string',
            'pediatrics_d' => 'string',
            'primary_diagnosis' => 'string',
            'diagnosis' => 'required',
            'prescription' => 'required',
            'follow_up_date' => 'date',
            'payment_amount' => 'required|integer',
            'payment_method' => 'required|string|in:cash,hmo',
            'payment_hmo' => 'string'
        ]);

        Gate::authorize('create', [ConsultationRecord::class, $request]);

        $user = $request->user();

        $fields['physician_id'] = $user->id;
        $fields['department_id'] = $user->department_id;

        $consultationRecord = ConsultationRecord::create($fields);

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
    
        return response()->json([
            'consultation_record' => $consultationRecord,
            'payment' => $payment
        ]);
    }

    public function show(Request $request, ConsultationRecord $consultationRecord)
    {
        // implements audit of retrieval
        event(new RetrievedData($request->user(), $consultationRecord, $request));

        return $consultationRecord;
    }

    public function update(Request $request, ConsultationRecord $consultationRecord)
    {
        Gate::authorize('update', $consultationRecord);

        $fields = $request->validate([
            'height' => 'decimal:0,2',
            'weight' => 'decimal:0,2',
            'blood_pressure' => 'string|max:7',
            'temperature' => 'decimal:0,2',
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
            'follow_up_date' => 'date',
        ]);

        $consultationRecord->update($fields);

        return $consultationRecord;
    }

    public function destroy(ConsultationRecord $consultationRecord)
    {
        $dateThreshold = Carbon::now()->subYears(10);

        //if patient or patient's last consultation date is not past 10 years, patient cannot be deleted
        if ($consultationRecord->updated_at >= $dateThreshold) 
        {
            return response()->json([
                'message' => 'Consultation record cannot be deleted 10 years before.'
            ], 403);
        }

        $consultationRecord->delete();

        return response()->json([
            'message' => 'Consultation record successfully deleted.'
        ]);
    }
}
