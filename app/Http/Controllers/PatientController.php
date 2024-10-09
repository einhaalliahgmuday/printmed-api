<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        return Patient::all();
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'patient_number' => 'required|integer|unique:patients',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'string|max:100',
            'last_name'=> 'required|string|max:100',
            'suffix' => 'string|max:20',
            'birthday' => 'date',
            'sex' => 'string|max:20',
            'address' => 'string|max:255',
            'civil_status' => 'string|max:20',
            'religion' => 'string|max:255',
            'phone_number' => 'string|max:30'
        ]);

        $patient = Patient::create($fields);

        return $patient;
    }

    public function show(Patient $patient)
    {
        return $patient;
    }

    public function update(Request $request, Patient $patient)
    {
        $fields = $request->validate([
            'patient_number' => 'required|integer|unique:patients',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'string|max:100',
            'last_name'=> 'required|string|max:100',
            'suffix' => 'string|max:20',
            'birthday' => 'date',
            'sex' => 'string|max:20',
            'address' => 'string|max:255',
            'civil_status' => 'string|max:20',
            'religion' => 'string|max:255',
            'phone_number' => 'string|max:30'
        ]);

        $patient->update($fields);

        return $patient;
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Patient successfully deleted.'
        ], 200);
    }
}
