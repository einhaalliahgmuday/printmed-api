<?php

namespace App\Http\Controllers;

use App\Events\PaymentNew;
use App\Models\ConsultationRecord;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PhysicianPatient;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ConsultationRecordController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();   //if user is doctor

        $fields = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'height' => 'decimal:0,2',
            'weight' => 'decimal:0,2',
            'blood_pressure' => 'string|max:7',
            'temperature' => 'decimal:0,2',
            'chief_complaint' => 'required',
            'primary_diagnosis' => 'string',
            'diagnosis' => 'required',
            'prescription' => 'required',
            'follow_up_date' => 'date',
            'payment_amount' => 'required|integer',
            'payment_method' => 'required|string|in:Cash,cash,HMO,hmo',
            'payment_hmo' => 'string'
        ]);

        $fields['physician_id'] = $user->id;
        $fields['physician_name'] = $user->full_name;
        $fields['department'] = $user->department;
        $patientName = Patient::select('full_name')->where('id', $request->patient_id)->first()->full_name;
        $date = Carbon::now('Asia/Manila');

        $paymentFields = [
            'date' => $date->toDateString(),
            'time' => $date->format('H:i'),
            'patient_id' => $request->patient_id,
            'patient_name' => $patientName,
            'amount' => $request->payment_amount,
            'method' => $request->payment_method,
            'physician_id' => $fields['physician_id'],
            'physician_name' => $fields['physician_name'],
            'department' => $fields['department']
        ];
        $paymentFields['hmo'] = $request->payment_hmo ? $request->payment_hmo : null;

        //creates the consultation and payment record
        $consultationRecord = ConsultationRecord::create($fields);
        $payment = Payment::create($paymentFields);

        event(new PaymentNew($payment));
        
        //updates last visit and follow-up dates of patient
        $followUpDate = $request->filled('follow_up_date') ? $request->follow_up_date : null;
        Patient::find($request->patient_id)->update([ 'last_visit' => $date->toDateString(), 'follow_up_date' => $followUpDate ]);

        //assign physician to the patient
        Patient::find($request->patient_id)->physicians()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'consultation_record' => $consultationRecord,
            'payment' => $payment
        ]);
    }

    public function show(Request $request, ConsultationRecord $consultationRecord)
    {
        $user = $request->user();

        if (in_array($user->role, ['physician', 'secretary']))
        {
            return $consultationRecord;
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized request.'
        ]);
    }

    public function update(Request $request, ConsultationRecord $consultationRecord)
    {
        $fields = $request->validate([
            'height' => 'decimal:0,2',
            'weight' => 'decimal:0,2',
            'blood_pressure' => 'string|max:7',
            'temperature' => 'decimal:0,2',
            'chief_complaint' => 'required|text',
            'history_of_present_illness' => 'text',
            'family_hx' => 'text',
            'medical_hx' => 'text',
            'pediatrics_h' => 'text',
            'pediatrics_e' => 'text',
            'pediatrics_a' => 'text',
            'pediatrics_d' => 'text',
            'diagnosis' => 'required|text',
            'prescription' => 'required|text',
        ]);

        $consultationRecord->update($fields);

        return $consultationRecord;
    }

    public function destroy(ConsultationRecord $consultationRecord)
    {
        $dateThreshold = Carbon::now()->subYears(10);

        //if consultation record is not past 10 years, record cannot be deleted
        if ($consultationRecord->created_at >= $dateThreshold) 
        {
            return response()->json([
                'success' => false,
                'message' => 'Consultation record cannot be deleted.'
            ], 403);
        }

        $consultationRecord->delete();

        return response()->json([
            'success' => true,
            'message' => 'Consultation record successfully deleted.'
        ]);
    }
}
