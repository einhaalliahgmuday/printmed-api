<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class PhysicianAccessController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'physician_id' => 'required|integer|exists:users,id'
        ]);

        $physician = User::where('id', $request->physician_id)->whereBlind('role', 'role_index', 'physician')->first();

        if (!$physician)
        {
            return response()->json([
                'message' => 'Physician not found.'
            ], 404);
        }

        $physician->patientAccesses()->syncWithoutDetaching([$request->patient_id]);

        return response()->json([
            
        ], 200);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'physician_id' => 'required|integer|exists:users,id'
        ]);

        $physician = User::where('id', $request->physician_id)->whereBlind('role', 'role_index', 'physician')->first();

        $physician->patientAccesses()->detach([$request->patient_id]);

        return response()->json([
            
        ], 200);
    }
}
