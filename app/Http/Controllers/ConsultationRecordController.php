<?php

namespace App\Http\Controllers;

use App\Models\ConsultationRecord;
use Illuminate\Http\Request;

class ConsultationRecordController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'physician_id' => 'sometimes|exists:users,id' //change to physicians
        ]);

        if ($request->has('physician_id')) 
        {
            return ConsultationRecord::where('physician_id', $request->query('physician_id'))->get();
        }

        return ConsultationRecord::all();
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
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
            'follow_up_date' => 'date',
            'physician_id' => 'integer|exists:users,id',
            'physician_name' => 'string'
        ]);

        $consultationRecord = ConsultationRecord::create($fields);

        return $consultationRecord;
    }

    public function show(ConsultationRecord $consultationRecord)
    {
        return $consultationRecord;
    }

    public function update(Request $request, ConsultationRecord $consultationRecord)
    {
        $fields = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
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
            'follow_up_date' => 'date',
            'physician_id' => 'integer|exists:users,id',
            'physician_name' => 'string'
        ]);

        $consultationRecord->update($fields);

        return $consultationRecord;
    }

    public function destroy(ConsultationRecord $consultationRecord)
    {
        $consultationRecord->delete();

        return response()->json([
            'success' => true,
            'message' => 'Patient successfully deleted.'
        ], 200);
    }
}
