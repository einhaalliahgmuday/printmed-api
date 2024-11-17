<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\VitalSigns;
use Illuminate\Http\Request;

class VitalSignsController extends Controller
{
    public function store(Request $request) 
    {
        $fields = $request->validate([
            'patient_id' => 'required|int|exists:patients,id',
            'height' => 'int',
            'height_unit' => 'string|in:cm,m',
            'weight' => 'int',
            'weight_unit' => 'string|in:kg,lb',
            'temperature' => 'int',
            'temperature_unit' => 'string|in:K,C',
            'blood_pressure' => 'string'
        ]);

        $patient = Patient::find($request->patient_id);

        if (!$patient) {
            return response(['message' => 'Invalid request'], 400);
        }

        if ($patient->vitalSigns->isNotEmpty()) {
            $patient->vitalSigns->each->delete();
        }

        $vitalSigns = VitalSigns::create($fields);

        return $vitalSigns;
    }

    public function update(Request $request, VitalSigns $vitalSigns) 
    {
        $fields = $request->validate([
            'height' => 'int',
            'height_unit' => 'string|in:cm,m',
            'weight' => 'int',
            'weight_unit' => 'string|in:kg,lb',
            'temperature' => 'int',
            'temperature_unit' => 'string|in:K,C',
            'blood_pressure' => 'string'
        ]);

        $vitalSigns->update($fields);

        return $vitalSigns;
    }

    public function destroy(VitalSigns $vitalSigns) 
    {
        $vitalSigns->delete();

        return response([
            'message' => "Vital signs record deleted successfully."
        ]);
    }
}
