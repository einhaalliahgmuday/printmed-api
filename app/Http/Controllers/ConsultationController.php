<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConsultationController extends Controller
{
    //store and update has policy implemented
    //only physicians of the patient can create a record
    //only the physician of a record can update the record

    // cannot get records if not a patient of user?
    public function index(Request $request, Patient $patient) {
        $request->validate([
            'date_from' => 'date|date_format:Y-m-d',
            'date_until' => 'date|date_format:Y-m-d|after_or_equal:date_from'
        ]);

        Gate::authorize('is-assigned-physician', [$patient->id]);

        $query = Consultation::query()
                            ->select('id', 'chief_complaint', 'primary_diagnosis', 'created_at')
                            ->where('patient_id', $patient->id)
                            ->where('department_id', $request->user()->department_id);

        if ($request->filled('date_from'))
        {
            $dateFrom = Carbon::parse($request->date_until)->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($request->filled('date_until'))
        {
            $dateUntil = Carbon::parse($request->date_until)->endOfDay();
            $query->where('created_at', '<=', $dateUntil);
        }

        return $query->orderBy('created_at')->paginate(15);
    }
    
    // cannot add records if not a patient of user
    public function store(Request $request)
    {
        $fields = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'height' => 'required|numeric|decimal:0,2',
            'height_unit' => 'required|string|in:cm,m|required_with:height',
            'weight' => 'required|numeric|decimal:0,2',
            'weight_unit' => 'required|string|in:kg,lb|required_with:weight',
            'temperature' => 'required|numeric|decimal:0,2',
            'temperature_unit' => 'required|string|in:K,C|required_with:temperature',
            'systolic' => 'required|string|max:3',
            'diastolic' => 'required|string|max:3',
            'chief_complaint' => 'required|string',
            'present_illness_hx' => 'nullable|string',
            'family_hx' => 'nullable|string',
            'medical_hx' => 'nullable|string',
            'pediatrics_h' => 'nullable|string',
            'pediatrics_e' => 'nullable|string',
            'pediatrics_a' => 'nullable|string',
            'pediatrics_d' => 'nullable|string',
            'primary_diagnosis' => 'required|string',
            'diagnosis' => 'required|string',
            'follow_up_date' => 'nullable|date|date_format:Y-m-d'
        ]);

        $request->validate([
            'prescriptions' => 'array',
            'prescriptions.*.name' => 'required_with:prescriptions|string|max:100',
            'prescriptions.*.dosage' => 'required_with:prescriptions|string|max:255',
            'prescriptions.*.instruction' => 'required_with:prescriptions|string|max:255',
        ]);

        Gate::authorize('is-assigned-physician', [$request->patient_id]);

        $user = $request->user();

        $fields['physician_id'] = $user->id;
        $fields['department_id'] = $user->department_id;

        $consultation = Consultation::create($fields);

        if ($request->filled('prescriptions')) {
            foreach($request->prescriptions as $prescription) 
            {
                Prescription::create([
                    'name' => $prescription['name'],
                    'dosage' => $prescription['dosage'],
                    'instruction' => $prescription['instruction'],
                    'consultation_id' => $consultation->id
                ]);
            }
        }

        $consultation['prescriptions'] = $consultation->prescriptions()->get();

        // audit creation of consultation and prescription
        event(new ModelAction(AuditAction::CREATE, $request->user(), $consultation, null, $request));
    
        return $consultation;
    }

    public function show(Request $request, Consultation $consultation)
    {
        Gate::authorize('is-assigned-physician', [$consultation->patient_id]);

        $consultation['prescriptions'] = $consultation->prescriptions()->get();

        // implements audit of retrieval
        event(new ModelAction(AuditAction::RETRIEVE, $request->user(), $consultation, null, $request));

        return $consultation;
    }

    // public function update(Request $request, Consultation $consultation)
    // {
    //     Gate::authorize('update', $consultation);

    //     $fields = $request->validate([
    //         'height' => 'required|numeric|decimal:0,2',
    //         'height_unit' => 'required|string|in:cm,m|required_with:height',
    //         'weight' => 'required|numeric|decimal:0,2',
    //         'weight_unit' => 'required|string|in:kg,lb|required_with:weight',
    //         'temperature' => 'required|numeric|decimal:0,2',
    //         'temperature_unit' => 'required|string|in:K,C|required_with:temperature',
    //         'blood_pressure' => 'required|string|max:7',
    //         'chief_complaint' => 'required|string',
    //         'present_illness_hx' => 'nullable|string',
    //         'family_hx' => 'nullable|string',
    //         'medical_hx' => 'nullable|string',
    //         'pediatrics_h' => 'nullable|string',
    //         'pediatrics_e' => 'nullable|string',
    //         'pediatrics_a' => 'nullable|string',
    //         'pediatrics_d' => 'nullable|string',
    //         'primary_diagnosis' => 'required|string',
    //         'diagnosis' => 'required|string'
    //     ]);

    //     $request->validate([
    //         'prescriptions' => 'nullable|array',
    //         'prescriptions.*.name' => 'required_with:prescriptions|string|max:100',
    //         'prescriptions.*.dosage' => 'required_with:prescriptions|string|max:255',
    //         'prescriptions.*.instruction' => 'required_with:prescriptions|string|max:255',
    //     ]);

    //     $originalData = $consultation->toArray();

    //     $consultation->update($fields);

    //     $prescriptions = $request->prescriptions;
    //     if ($prescriptions && count($prescriptions) > 0) 
    //     {
    //         Prescription::where('consultation_id', $consultation->id)->delete();
    //         foreach($prescriptions as $prescription) 
    //         {
    //             Prescription::create([
    //                 'name' => $prescription['name'],
    //                 'dosage' => $prescription['dosage'],
    //                 'instruction' => $prescription['instruction'],
    //                 'consultation_id' => $consultation->id
    //             ]);
    //         }
    //     }

    //     event(new ModelAction(AuditAction::UPDATE, $request->user(), $consultation, $originalData, $request));

    //     return $consultation;
    // }

    // public function destroy(Consultation $consultation)
    // {
    //     $dateThreshold = now()->subYears(10);

    //     //if patient or patient's last consultation date is not past 10 years, patient cannot be deleted
    //     if ($consultation->updated_at >= $dateThreshold) 
    //     {
    //         return response()->json([
    //             'message' => 'Consultation record cannot be deleted 10 years before.'
    //         ], 403);
    //     }

    //     $consultation->prescriptions()->delete();
    //     $consultation->delete();

    //     return response()->json([
    //         'message' => 'Consultation record successfully deleted.'
    //     ]);
    // }
}
