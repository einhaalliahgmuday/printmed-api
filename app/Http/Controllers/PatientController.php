<?php

namespace App\Http\Controllers;

use App\Events\RetrievedData;
use App\Models\Patient;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {        
        $user = $request->user();

        $request->validate([
            'search' => 'string',
            'sex' => 'string',
            'sort_by' => 'string|in:last_name,patient_number,follow_up_date',
            'sort_direction' => 'string|in:asc,desc'
        ]);

        
        //if role is physician, it will only query the patients of the physician
        $query = $user->role == 'physician' ? $user->patients() : Patient::query();

        //query can be filter based on search (name, patient_number), sex
        //it can also be sorted by last_name, patient_number, and follow_up_date
        if ($query != null)
        {
            if($request->filled('search') )
            {
                $search = $request->search;

                $query->where(function($q) use ($search)
                {
                    $q->where('first_name', 'LIKE', "%$search%")
                    ->orWhere('middle_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%")
                    ->orWhere('suffix', 'LIKE', "%$search%")
                    ->orWhere('patient_number', 'LIKE', "%$search%");
                });
            }

            if($request->filled('sex'))
            {
                $query->where('sex', $request->sex);
            }

            if($request->filled('sort_by') && in_array($request->sort_by, ['last_name', 'patient_number', 'follow_up_date'])) 
            {
                $direction = $request->input('sort_direction', 'asc');

                $query->orderBy($request->input('sort_by'), $direction);
            } 
            else {
                $query->orderBy('updated_at');
            }

            $patients = $query->paginate(15);
            $patients->appends($request->all()); //appends the request parameters to pagination URLs

            // implements audit of retrieval
            // event(new RetrievedData($user, $patients->getCollection(), $request));

            return $patients;
        }

        return response()->json(['patients' => null]);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'first_name' => 'required|string|max:100',
            'middle_name' => 'string|max:100',
            'last_name'=> 'required|string|max:100',
            'suffix' => 'string|max:20',
            'birthdate' => 'date',
            'birthplace' => 'string',
            'sex' => 'string|max:20',
            'address' => 'string|max:255',
            'civil_status' => 'string|max:20',
            'religion' => 'string|max:255',
            'phone_number' => 'string|max:30'
        ]);

        $fields['patient_number'] = Patient::generatePatientNumber();

        $patient = Patient::create($fields);

        return $patient;
    }

    public function show(Request $request, Patient $patient)
    {
        $consultationRecords = $patient->consultationRecords()->paginate(10);

        // implements audit of retrieval
        // event(new RetrievedData($request->user, $consultationRecords->getCollection(), $request));
        event(new RetrievedData($request->user(), $patient, $request));

        return response()->json([
            'patient' => $patient,
            'consultationRecords' => $consultationRecords
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $fields = $request->validate([
            'first_name' => 'string|max:100',
            'middle_name' => 'string|max:100',
            'last_name'=> 'string|max:100',
            'suffix' => 'string|max:20',
            'birthdate' => 'date',
            'birthplace' => 'string',
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
        $dateThreshold = Carbon::now()->subYears(10);
        $lastConsultationRecordDate = $patient->consultationRecords()
                                            ->select('updated_at')
                                            ->orderBy('updated_at','desc')
                                            ->pluck('updated_at')
                                            ->first();

        //if patient or patient's last consultation date is not past 10 years, patient cannot be deleted
        if ($patient->updated_at >= $dateThreshold || $lastConsultationRecordDate >= $dateThreshold) 
        {
            return response()->json([
                'message' => 'Patient record cannot be deleted 10 years before last record.'
            ], 403);
        }

        $patient->delete();

        return response()->json([
            'message' => 'Patient successfully deleted.'
        ], 200);
    }

    public function getDuplicates(Request $request) 
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'=> 'required|string|max:100',
            'birthdate' => 'required|date',
            'sex' => 'required|string|max:6'
        ]);

        $patients = Patient::where('first_name', 'LIKE', "%$request->first_name%")
                            ->where('last_name', $request->last_name)
                            ->where('birthdate', $request->birthdate)
                            ->where('sex', $request->sex)
                            ->get();

        return $patients;
    }

    public function getCount(Request $request)
    {
        $request->validate([
            'department_id' => 'integer|exists:departments,id',
            'date_from' => 'date',
            'date_until' => 'date|after_or_equal:date_from',
        ]);

        //uses Payment model to get the number of patients visited
        $query = Payment::query();

        if($request->filled('department_id'))
        {
            $query->where('department_id', $request->department);
        }

        if($request->filled('date_from'))
        {
            $query->where('date', '>=', $request->date_from);
        }

        if($request->filled('date_until'))
        {
            $query->where('date', '<=', $request->date_until);
        }

        $query->distinct('patient_id');

        $patientsCount = $query->count();
        
        return response()->json([
            'patients_count' => $patientsCount
        ]);
    }
}
