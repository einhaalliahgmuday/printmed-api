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
            'height' => 'numeric|decimal:0,2',
            'height_unit' => 'string|in:cm,m|required_if:height',
            'weight' => 'numeric|decimal:0,2',
            'weight_unit' => 'string|in:kg,lb|require_if:weight',
            'temperature' => 'numeric|decimal:0,2',
            'temperature_unit' => 'string|in:K,C|required_if:temperature',
            'blood_pressure' => 'string|max:7',
        ]);

        $patient = Patient::find($request->patient_id);

        if (!$patient) {
            return response(['message' => 'Invalid request'], 400);
        }

        if ($patient->vitalSigns->isNotEmpty()) {
            $patient->vitalSigns->each->delete();
        }

        if (!$fields['height'] && !$fields['weight'] && !$fields['temperature'] && !$fields['blood_pressure']) {
            return response(['message' => 'All fields for vital signs are empty.'], 400);
        }

        $vitalSigns = VitalSigns::create($fields);

        return $vitalSigns;
    }

    public function update(Request $request, VitalSigns $vitalSigns) 
    {
        $fields = $request->validate([
            'height' => 'numeric|decimal:0,2',
            'height_unit' => 'string|in:cm,m|required_if:height',
            'weight' => 'numeric|decimal:0,2',
            'weight_unit' => 'string|in:kg,lb|require_if:weight',
            'temperature' => 'numeric|decimal:0,2',
            'temperature_unit' => 'string|in:K,C|required_if:temperature',
            'blood_pressure' => 'string|max:7',
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
