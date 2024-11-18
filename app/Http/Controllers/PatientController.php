<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use App\Events\PatientNew;
use App\Events\PatientUpdated;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Registration;
use App\Traits\CommonMethodsTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class PatientController extends Controller
{
    use CommonMethodsTrait;

    public function index(Request $request)
    {        
        $user = $request->user();

        $request->validate([
            'search' => 'string',
            'sort_by' => 'string|in:last_name,patient_number,follow_up_date',
            'sort_direction' => 'string|in:asc,desc'
        ]);

        
        //if role is physician, it will only query the patients of the physician
        $query = $user->role == 'physician' ? $user->patients() : Patient::query()->select('id', 'patient_number', 'first_name', 'last_name', 'full_name', 'birthdate', 'sex', 'created_at');

        //query can be filter based on search (name, patient_number), sex
        //it can also be sorted by last_name, patient_number, and follow_up_date
        
        if ($request->filled('search')) 
        {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                $q->whereBlind('patient_number', 'patient_number_index', $search)
                ->orWhereBlind('full_name', 'full_name_index', $search);
            });
        }

        if($request->filled('sex'))
        {
            $query->whereBlind('sex', 'sex_index', $request->sex);
        }

        $query->orderBy('patients.updated_at', 'desc');
        $patients = $query->get();

        if (count($patients) > 0)
        {
            if($request->filled('sort_by') && in_array($request->sort_by, ['last_name', 'patient_number', 'follow_up_date'])) 
            {
                $isDesc = $request->input('sort_direction') == 'desc';

                $patients = $patients->sortBy($request->sort_by, SORT_REGULAR, $isDesc)->values();
            } 

            $page = $request->input('page',1);
            $data = array_slice($patients->toArray(), ($page - 1) * 15, 15);
            $paginator = new LengthAwarePaginator(
                $data, 
                count($patients), 
                15, 
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $paginator->appends($request->query());

            return $paginator;
        }

        return response()->json(['patients' => null]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $fields = $request->validate([
            'first_name' => 'required|string|max:100',
            'middle_name' => 'string|max:100',
            'last_name'=> 'required|string|max:100',
            'suffix' => 'string|max:20',
            'birthdate' => 'date|date_format:Y-m-d',
            'birthplace' => 'string',
            'sex' => 'string|max:6|nullable',
            'house_number' => 'string|max:100',
            'street' => 'string|max:100',
            'barangay' => 'string|max:100',
            'city' => 'string|max:100',
            'province' => 'string|max:100',
            'postal_code' => 'string|max:100',
            'civil_status' => 'string|max:20',
            'religion' => 'string|max:100',
            'phone_number' => 'string|max:12',
            'email' => 'email|max:100',
            'registration_id' => 'int|exist:registrations,id'
        ]);

        $request->validate([
            'photo' => 'image|mimes:png|max:2048|dimensions:min_width=200,min_height=200'
        ]);

        $fields['patient_number'] = Patient::generatePatientNumber();
        $fields['full_name'] = $this->getFullName($request->first_name, $request->last_name);

        $patient = Patient::create($fields);

        if ($request->filled('photo')) {
            $path = $request->file('photo')->store('images/patients', ['local', 'private']);
            $patient->update(['photo' => $path]);
        }

        // delete record at registrations
        if ($request->filled('registration_id')) {
            $registration = Registration::find($request->registration_id);

            if ($registration) {
                $registration->delete();
            }
        }

        // audit creation of patient
        event(new ModelAction(AuditAction::CREATE, $request->user(), $patient, null, $request));

        if ($user->role == 'physician')
        {
            $patient->physicians()->syncWithoutDetaching([$user->id]);
        }

        // pusher event
        event(new PatientNew($patient));

        return $patient;
    }

    public function show(Request $request, Patient $patient)
    {
        // implements audit of patient retrieval
        event(new ModelAction(AuditAction::RETRIEVE, $request->user(), $patient, null, $request));

        return $patient;
    }

    public function getPhoto(Patient $patient)
    {
        $path = $patient->photo;

        if ($path != null) {
            if (!Storage::exists($path)) {
                return response()->json(['error' => 'Photo not found'], 404);
            }
    
            $mimeType = Storage::mimeType($path);
    
            return response()->file(Storage::get($path), [
                'Content-Type' => $mimeType
            ]);
        }

        return response()->json([
            'message' => 'Patient has no photo.'
        ], 400);
    }

    public function update(Request $request, Patient $patient)
    {
        $fields = $request->validate([
            'first_name' => 'string|max:100',
            'middle_name' => 'string|max:100',
            'last_name'=> 'string|max:100',
            'suffix' => 'string|max:20',
            'birthdate' => 'date|date_format:Y-m-d',
            'birthplace' => 'string',
            'sex' => 'string|max:6',
            'house_number' => 'string|max:100',
            'street' => 'string|max:100',
            'barangay' => 'string|max:100',
            'city' => 'string|max:100',
            'province' => 'string|max:100',
            'postal_code' => 'string|max:100',
            'civil_status' => 'string|max:20',
            'religion' => 'string|max:100',
            'phone_number' => 'string|max:12',
            'email' => 'email|max:100',
        ]);

        $originalData = $patient->toArray();

        $patient->update($fields);

        if ($request->filled('first_name') || $request->filled('last_name'))
        {
            $patient->update(['full_name' => $this->getFullName($patient->first_name, $patient->last_name)]);
        }

        // implements audit of update
        event(new ModelAction(AuditAction::UPDATE, $request->user(), $patient, $originalData, $request));

        // pusher event
        event(new PatientUpdated($patient));

        return $patient;
    }

    public function updatePhoto(Request $request, Patient $patient) {
        $request->validate([
            'image' => 'required|image|mimes:png|max:2048|dimensions:min_width=200,min_height=200'
        ]);
        
        if($patient->photo != null) {
            Storage::delete($patient->photo);;
        }

        $path = $request->file('image')->store('images/patients', ['local', 'private']);
        $patient->update(['photo' => $path]);

        $file = Storage::get($path);
        $mimeType = Storage::mimeType($path);

        // pusher event
        // event(new PatientUpdated($patient));

        return response()->file($file, [
            'Content-Type' => $mimeType
        ]);
    }

    public function destroy(Patient $patient)
    {
        $dateThreshold = now()->subYears(10);
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
            'birthdate' => 'required|date|date_format:Y-m-d',
            'sex' => 'required|string|max:6'
        ]);

        $patients = Patient::select('id', 'patient_number', 'full_name', 'birthdate', 'sex')
                            ->whereBlind('first_name', 'first_name_index', $request->first_name)
                            ->whereBlind('last_name', 'last_name_index', $request->last_name)
                            ->whereBlind('birthdate', 'birthdate_index', $request->birthdate)
                            ->whereBlind('sex', 'sex_index', $request->sex)
                            ->get();

        return $patients;
    }
}
