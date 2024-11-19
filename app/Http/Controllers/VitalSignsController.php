<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\VitalSigns;
use Illuminate\Http\Request;

class VitalSignsController extends Controller
{
    public function store(Request $request, Patient $patient) 
    {
        $fields = $request->validate([
            'height' => 'required|numeric|decimal:0,2',
            'height_unit' => 'required|string|in:cm,m',
            'weight' => 'required|numeric|decimal:0,2',
            'weight_unit' => 'required|string|in:kg,lb',
            'temperature' => 'required|numeric|decimal:0,2',
            'temperature_unit' => 'required|string|in:K,C',
            'blood_pressure' => 'required|string|max:7',
        ]);

        if ($patient->vitalSigns->isNotEmpty()) {
            $patient->vitalSigns->each->delete();
        }

        if (!$fields['height'] && !$fields['weight'] && !$fields['temperature'] && !$fields['blood_pressure']) {
            return response(['message' => 'All fields are empty.'], 400);
        }

        $patient->vitalSigns()->create($fields);

        return $patient;
    }

    public function update(Request $request, VitalSigns $vitalSigns) 
    {
        $fields = $request->validate([
            'height' => 'numeric|decimal:0,2',
            'height_unit' => 'string|in:cm,m|required_with:height',
            'weight' => 'numeric|decimal:0,2',
            'weight_unit' => 'string|in:kg,lb|required_with:weight',
            'temperature' => 'numeric|decimal:0,2',
            'temperature_unit' => 'string|in:K,C|required_with:temperature',
            'blood_pressure' => 'string|max:7',
        ]);

        if (!$fields['height'] && !$fields['weight'] && !$fields['temperature'] && !$fields['blood_pressure']) {
            return response(['message' => 'All fields are empty.'], 400);
        }

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
