<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PhysicianPatientRelationship;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'physician_id' => 'integer|exists:users,id'
        ]);

        //returns patients of a specific physician
        if ($request->has('physician_id')) 
        {
            return User::find($request->physician_id)->first()->patients;
        }
        
        //returns all patients
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
            // 'patient_number' => 'required|integer|unique:patients',
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
        $dateThreshold = Carbon::now()->subYears(10)->toDateString();

        //if patient's last consultation date is not past 10 years, patient cannot be deleted
        if ($patient->last_consultation >= $dateThreshold) 
        {
            return response()->json([
                'success' => false,
                'message' => 'Patient records cannot be deleted.'
            ], 403);
        }

        $patient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Patient successfully deleted.'
        ]);
    }
}
