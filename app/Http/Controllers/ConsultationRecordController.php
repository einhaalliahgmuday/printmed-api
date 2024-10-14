<?php

namespace App\Http\Controllers;

use App\Models\ConsultationRecord;
use App\Models\Patient;
use App\Models\PhysicianPatient;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ConsultationRecordController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'physician_id' => 'sometimes|integer|exists:users,id'
        ]);

        //return consultation records by a specific physician
        //for if doctors must ONLY see records they written
        if ($request->has('physician_id')) 
        {
            return ConsultationRecord::where('physician_id', $request->physician_id)
                                    ->where('patient_id', $request->patient_id)
                                    ->get();
        }

        //returns all consultation records of patient
        return ConsultationRecord::where('patient_id', $request->input('patient_id'))->get();
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'height' => 'decimal:0,2',
            'weight' => 'decimal:0,2',
            'blood_pressure' => 'string|max:7',
            'temperature' => 'decimal:0,2',
            'chief_complaint' => 'required',
            'diagnosis' => 'required',
            'prescription' => 'required',
            'follow_up_date' => 'date',
            'physician_id' => 'integer|exists:users,id',
            'physician_name' => 'string',
        ]);

        //creates the consultation record
        $consultationRecord = ConsultationRecord::create($fields);
        
        $date = Carbon::now('Asia/Manila')->toDateString();
        //updates last consultation date of patient
        Patient::find($request->patient_id)->update([ 'last_visit' => $date ]);

        //checks if physician-patient relationship already exists in the pivot table
        //else inserts the relationship
        if ($request->physician_id) 
        {
            $isRelationshipExists = PhysicianPatient::where('physician_id', $request->physician_id)
                                                                ->where('patient_id', $request->patient_id)->exists();
            
            if (!$isRelationshipExists) 
            {
                PhysicianPatient::create([
                    'physician_id' => $request->physician_id,
                    'patient_id' => $request->patient_id
                ]);
            }
        }

        return $consultationRecord;
    }

    public function show(ConsultationRecord $consultationRecord)
    {
        return $consultationRecord;
    }

    public function update(Request $request, ConsultationRecord $consultationRecord)
    {
        $fields = $request->validate([
            // 'patient_id' => 'required|integer|exists:patients,id',
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
            // 'follow_up_date' => 'date',
            // 'physician_id' => 'integer|exists:users,id',
            // 'physician_name' => 'string'
        ]);

        $consultationRecord->update($fields);

        return $consultationRecord;
    }

    public function destroy(ConsultationRecord $consultationRecord)
    {
        $dateThreshold = Carbon::now()->subYears(10);

        //if consultation record is not past 10 years, patient cannot be deleted
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
