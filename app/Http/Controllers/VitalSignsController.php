<?php

namespace App\Http\Controllers;

use App\Events\VitalSignsNew;
use App\Events\VitalSignsUpdated;
use App\Models\Patient;
use App\Models\VitalSigns;
use Illuminate\Http\Request;

class VitalSignsController extends Controller
{
    public function store(Request $request, Patient $patient) 
    {
        $fields = $request->validate([
            'height' => 'required|numeric|decimal:0,2',
            'height_unit' => 'required|string|in:cm,m|required_with:height',
            'weight' => 'required|numeric|decimal:0,2',
            'weight_unit' => 'required|string|in:kg,lb|required_with:weight',
            'temperature' => 'required|numeric|decimal:0,2',
            'temperature_unit' => 'required|string|in:K,C|required_with:temperature',
            'systolic' => 'required|string|max:3',
            'diastolic' => 'required|string|max:3',
        ]);

        if (!$fields['height'] && !$fields['weight'] && !$fields['temperature'] && !$fields['blood_pressure']) {
            return response(['message' => 'All fields are empty.'], 400);
        }

        $vitalSigns = $patient->vitalSigns()->create($fields);

        VitalSignsNew::dispatch($vitalSigns);

        return $vitalSigns;
    }

    public function update(Request $request, $id) 
    {
        $fields = $request->validate([
            'height' => 'numeric|decimal:0,2',
            'height_unit' => 'string|in:cm,m',
            'weight' => 'numeric|decimal:0,2',
            'weight_unit' => 'string|in:kg,lb',
            'temperature' => 'numeric|decimal:0,2',
            'temperature_unit' => 'string|in:K,C',
            'systolic' => 'string|max:3',
            'diastolic' => 'string|max:3',
        ]);

        $vitalSigns = VitalSigns::find($id);

        if ($vitalSigns) {
            $vitalSigns->update($fields);

            VitalSignsUpdated::dispatch($vitalSigns);

            return $vitalSigns;
        }

        return response([
            'message' => "Vital signs record not found."
        ], 404);
    }

    public function destroy($id) 
    {
        $vitalSigns = VitalSigns::find($id);

        if ($vitalSigns) {
            $vitalSigns->delete();

            return response([
                'message' => "Vital signs record deleted successfully."
            ]);
        }
    }
}
