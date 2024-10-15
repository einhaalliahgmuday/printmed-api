<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PhysicianPatient;
use Illuminate\Http\Request;

class PhysicianPatientController extends Controller
{
    public function assignPatientPhysician(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'physicians' => 'required|array',
            'physicians.*.physician_id' => 'required|integer|exists:users,id'
        ]);

        $physicianIds = array_unique(array_column($request->physicians, 'physician_id'));

        $patient = Patient::findOrFail($request->patient_id);
        $patient->physicians()->syncWithoutDetaching($physicianIds);

        return response()->json([
            'success' => true,
            'message' => 'Physicians assigned to patients successfully.'
        ], 200);
    }
}
