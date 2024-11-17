<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Prescription;
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
            'height_unit' => 'string|in:cm,m|required_with:height',
            'weight' => 'numeric|decimal:0,2',
            'weight_unit' => 'string|in:kg,lb|required_with:weight',
            'temperature' => 'numeric|decimal:0,2',
            'temperature_unit' => 'string|in:K,C|required_with:temperature',
            'blood_pressure' => 'string|max:7',
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
            'follow_up_date' => 'date|date_format:Y-m-d'
        ]);

        $request->validate([
            'prescriptions' => 'array',
            'prescriptions.*.name' => 'required_with:prescriptions|string|max:100',
            'prescriptions.*.dosage' => 'required_with:prescriptions|string|max:255',
            'prescriptions.*.instruction' => 'required_with:prescriptions|string|max:255',
        ]);

        $prescriptions = $request->prescriptions;

        Gate::authorize('create', [Consultation::class, $request]);

        $user = $request->user();

        $fields['physician_id'] = $user->id;
        $fields['department_id'] = $user->department_id;

        $consultation = Consultation::create($fields);

        if ($prescriptions && count($prescriptions) > 0) 
        {
            foreach($prescriptions as $prescription) 
            {
                Prescription::create([
                    'name' => $prescription['name'],
                    'dosage' => $prescription['dosage'],
                    'instruction' => $prescription['instruction'],
                    'consultation_id' => $consultation->id
                ]);
            }
        }

        // audit creation of consultation and prescription
        event(new ModelAction(AuditAction::CREATE, $request->user(), $consultation, null, $request));
    
        return $consultation;
    }

    public function show(Request $request, Consultation $consultation)
    {
        Gate::authorize('view', $consultation);

        // implements audit of retrieval
        event(new ModelAction(AuditAction::RETRIEVE, $request->user(), $consultation, null, $request));

        return $consultation;
    }

    public function update(Request $request, Consultation $consultation)
    {
        Gate::authorize('update', $consultation);

        $fields = $request->validate([
            'height' => 'nullable|numeric|decimal:0,2',
            'height_unit' => 'nullable|string|in:cm,m|required_with:height',
            'weight' => 'numeric|decimal:0,2',
            'weight_unit' => 'string|in:kg,lb|required_with:weight',
            'temperature' => 'numeric|decimal:0,2',
            'temperature_unit' => 'string|in:K,C|required_with:temperature',
            'blood_pressure' => 'string|max:7',
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
            'follow_up_date' => 'date|date_format:Y-m-d'
        ]);

        $request->validate([
            'prescriptions' => 'array',
            'prescriptions.*.name' => 'required_with:prescriptions|string|max:100',
            'prescriptions.*.dosage' => 'required_with:prescriptions|string|max:255',
            'prescriptions.*.instruction' => 'required_with:prescriptions|string|max:255',
        ]);

        $originalData = $consultation->toArray();

        $consultation->update($fields);

        $prescriptions = $request->prescriptions;
        if ($prescriptions && count($prescriptions) > 0) 
        {
            Prescription::where('consultation_id', $consultation->id)->delete();
            foreach($prescriptions as $prescription) 
            {
                Prescription::create([
                    'name' => $prescription['name'],
                    'dosage' => $prescription['dosage'],
                    'instruction' => $prescription['instruction'],
                    'consultation_id' => $consultation->id
                ]);
            }
        }

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
