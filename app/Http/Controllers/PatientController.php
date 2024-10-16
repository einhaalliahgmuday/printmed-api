<?php

namespace App\Http\Controllers;

use App\Models\ConsultationRecord;
use App\Models\Patient;
use App\Models\PhysicianPatient;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use CommonMethodsTrait;

    public function index(Request $request)
    {        
        $user = $request->user();

        $request->validate([
            'search' => 'string',
            'sex' => 'string'
        ]);

        //only physician and secretary can only access patient records
        if (in_array($user->role, ['physician', 'secretary'])) 
        {
            //if role is physician, it will only query the patients of it; else, all
            $query = $user->role === 'physician' ? $user->patients() : Patient::query();

            if ($query != null)
            {
                //narrows the query with search
                if($request->filled('search') )
                {
                    $search = $request->search;

                    $query->where(function($q) use ($search)
                    {
                        $q->where('full_name', 'LIKE', "%$search%")
                        ->orWhere('patient_id', 'LIKE', "%$search%")
                        ->orWhere('birthday', 'LIKE', "%$search%");
                    });
                }

                //narrows the query with sex
                if($request->filled('sex'))
                {
                    $query->where('sex', $request->sex);
                }

                //sort the query by either last_name, patient_id, or birthday
                if($request->filled('sort_by') && in_array($request->sort_by, ['last_name', 'patient_id', 'birthdate', 'follow_up_date'])) 
                {
                    $direction = $request->input('sort_direction', 'asc');

                    $query->orderBy($request->input('sort_by'), $direction);
                } 
                else {
                    $query->orderBy('updated_at');
                }

                $patients = $query->select('patients.id', 'patients.patient_id', 'patients.full_name', 'patients.suffix', 'patients.birthday', 'patients.sex', 'patients.last_visit')->paginate(15);
                $patients->appends($request->all()); //appends the request parameters to paginate URLs

                return $patients;
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized request.'
        ], 401);
    }

    public function isPatientExists(Request $request) 
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'=> 'required|string|max:100',
            'birthday' => 'date',
            'sex' => 'string|max:20'
        ]);

        $patients = Patient::select('id', 'patient_id', 'full_name', 'suffix', 'birthday', 'sex', 'last_visit')
                            ->where('first_name', 'LIKE', "%$request->first_name%")
                            ->where('last_name', $request->last_name)
                            ->where('birthday', $request->birthday)
                            ->where('sex', $request->sex)
                            ->get();

        if ($patients->count() <= 0) 
        {
            return response()->json([
                'exists' => false
            ]);
        }

        return $patients;
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
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

        $fields['full_name'] = $this->getFullName($request);
        $fields['last_visit'] = Carbon::now('Asia/Manila')->toDateString();
        $fields['patient_id'] = Patient::generatePatientNumber();

        $patient = Patient::create($fields);

        return $patient;
    }

    public function show(Request $request, Patient $patient)
    {
        $user = $request->user();

        $consultationRecords = ConsultationRecord::select('id', 'chief_complaint', 'primary_diagnosis', 'created_at')
                                                ->where('patient_id', $patient->id);

        //to return consultation records by a specific physician only
        if ($user->role === 'physician') 
        {
            $consultationRecords->where('physician_id', $user->id);
        }

        $consultationRecords = $consultationRecords->get();

        return response()->json([
            'patient' => $patient,
            'consultation_records' => $consultationRecords
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $fields = $request->validate([
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

        $fields['full_name'] = $this->getFullName($request);

        $patient->update($fields);

        return $patient;
    }

    public function destroy(Patient $patient)
    {
        $dateThreshold = Carbon::now()->subYears(10)->toDateString();

        //if patient's last consultation date is not past 10 years, patient cannot be deleted
        if ($patient->last_visit >= $dateThreshold) 
        {
            return response()->json([
                'success' => false,
                'message' => 'Patient record cannot be deleted 10 years before last record.'
            ], 403);
        }

        $patient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Patient successfully deleted.'
        ], 200);
    }
}
