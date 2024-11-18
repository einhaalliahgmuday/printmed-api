<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

class PatientPhysicianController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $request->validate([
            'physician_id' => 'required|integer|exists:users,id'
        ]);

        $isPhysicianExists = User::where('id', $request->physician_id)->whereBlind('role', 'role_index', 'physician')->exists();

        if (!$isPhysicianExists)
        {
            return response()->json([
                'message' => 'Physician not found.'
            ], 404);
        }

        $patient->physicians()->syncWithoutDetaching([$request->physician_id]);

        // returns patient's physicians
        return response()->json([
            'patient_physicians' => $patient->physicians
        ], 200);
    }

    public function destroy(Request $request, Patient $patient)
    {
        $request->validate([
            'physician_id' => 'required|integer|exists:patient_physicians,physician_id'
        ]);

        $patient->physicians()->detach([$request->physician_id]);

        // returns patient's physicians
        return response()->json([
            'patient_physicians' => $patient->physicians
        ], 200);
    }
}
