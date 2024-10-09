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
        $request->validate([
            'first_name' => 'required|max:100',
            'middle_name' => 'max:100',
            'last_name'=> 'required|max:100',
            'suffix' => 'max:20',
            'birthday' => 'date',
            'sex' => 'max:20',
            'address' => 'max:255',
            'civil_status' => 'max:20',
            'religion' => 'max:255',
            'phone_number' => 'max:40'
        ]);

        $patient = Patient::create($request->all());

        return $patient;
    }

    public function show(Patient $patient)
    {
        return $patient;
    }

    public function update(Request $request, Patient $patient)
    {
        $request->validate([
            'first_name' => 'max:100',
            'middle_name' => 'max:100',
            'last_name'=> 'max:100',
            'suffix' => 'max:20',
            'birthday' => 'date',
            'sex' => 'max:20',
            'address' => 'max:255',
            'civil_status' => 'max:20',
            'religion' => 'max:255',
            'phone_number' => 'max:40'
        ]);

        $patient->update($request->all());

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
