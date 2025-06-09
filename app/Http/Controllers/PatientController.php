<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use App\Events\RegistrationDeleted;
use App\Models\Patient;
use App\Models\PatientPhysician;
use App\Models\PatientQr;
use App\Models\Registration;
use App\Models\User;
use App\Services\AmazonRekognitionService;
use App\Traits\CommonMethodsTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PatientController extends Controller
{
    use CommonMethodsTrait;

    private $rekognitionService;

    public function __construct() {
        $this->rekognitionService = new AmazonRekognitionService();
    }
    
    public function index(Request $request)
    {        
        $user = $request->user();

        $request->validate([
            'page' => 'integer',
            'search' => 'string',   // patient number, full name, first name, last name
            'sort_by' => 'string|in:name,patient_number,follow_up_date',
            'order_by' => 'string|in:asc,desc'
        ]);

        
        $query = Patient::query()->select('id', 'patient_number', 'first_name', 'middle_name', 'last_name', 'suffix', 'full_name', 'birthdate', 'sex', 'created_at');
        
        if ($request->filled('search')) 
        {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                $q->whereBlind('patient_number', 'patient_number_index', $search)
                ->orWhereBlind('full_name', 'full_name_index', $search)
                ->orWhereBlind('first_name', 'first_name_index', $search)
                ->orWhereBlind('last_name', 'last_name_index', $search);
            });
        }

        $query->orderBy('updated_at', 'desc');
        $patients = $query->get()
                            ->makeHidden(['qr_status', 'vital_signs']); // hides some attributes

        foreach($patients as $patient) {
            $patient['follow_up_date'] = $patient->getFollowUpDate($user->department_id);
        }

        if (count($patients) > 0)
        {
            if($request->filled('sort_by')) 
            {
                $isDesc = $request->input('order_by', 'desc') == 'desc';

                if ($request->sort_by == "name") {
                    $patients = $patients->sortBy('first_name', SORT_REGULAR, $isDesc)->values();
                    $patients = $patients->sortBy('last_name', SORT_REGULAR, $isDesc)->values();
                } else {
                    $patients = $patients->sortBy($request->sort_by, SORT_REGULAR, $isDesc)->values();
                }
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
            'middle_name' => 'nullable|string|max:100',
            'last_name'=> 'required|string|max:100',
            'suffix' => 'nullable|string|max:20',
            'birthdate' => 'required|date|date_format:Y-m-d',
            'birthplace' => 'required|string',
            'sex' => 'required|string|max:6',
            'house_number' => 'string|max:50',
            'street' => 'nullable|string|max:20',
            'barangay' => 'required|string|max:50',
            // 'barangay_code' => 'required|string|max:10',
            'city' => 'required|string|max:50',
            'city_code' => 'required|string|max:10',
            'province' => 'string|max:50',
            'province_code' => 'string|max:10',
            'region' => 'required|string|max:50',
            'region_code' => 'required|string|max:10',
            'postal_code' => 'nullable|int|digits_between:1,4',
            'civil_status' => 'required|string|max:20',
            'religion' => 'nullable|string|max:100',
            'phone_number' => 'string|size:10',
            'email' => 'nullable|email|max:100',
            'payment_method' => 'required|string|in:Cash,HMO',
            'hmo' => 'required_if:payment_method,HMO|string',
            'registration_id' => 'int|exists:registrations,id',
        ]);
        $request->validate([
            'photo' => 'required|image|mimes:jpg,png|max:2048|dimensions:min_width=640,min_height=480',
            'physician_id' => 'required|int|exists:users,id'
        ]);

        // checks if assigned physician exists
        $physician = User::where('id', $request->physician_id)
                        ->whereBlind('role', 'role_index', 'physician')
                        ->select('id', 'full_name', 'first_name', 'middle_name', 'last_name', 'suffix')
                        ->first();
        if (!$physician) {
            return response()->json([
                'message' => 'Physician not found.'
            ], 400);
        }

        // validates patient's photo quality
        $photo = $request->file('photo');
        $imageBytes = file_get_contents($photo->getRealPath());
        if(!$this->isQualityIdentificationPhoto($imageBytes, $this->rekognitionService)) {
            return response()->json([
                'message' => 'Invalid Photo'
            ], 400);
        }

        // creates the patient
        $fields['patient_number'] = Patient::generatePatientNumber();
        $fields['full_name'] = "{$request->first_name} {$request->last_name}";
        $patient = Patient::create($fields);
        $path = $photo->store('images/patients', ['local', 'private']);
        $patient->update(['photo' => $path]);
        $patient->physicians()->syncWithoutDetaching([$request->physician_id => ['department_id' => $user->department_id]]);

        // index face to rekognition collection
        $result = $this->rekognitionService->indexFaces($imageBytes, $patient->patient_number, 'patients');
        if($result['success'] == true) {
            if(count($result['result']) > 0) {
                $patient->update(['rekognitionFaceId' => $result['result']['Face']['FaceId']]);
            }
        }

        // delete record at registrations
        if ($request->filled('registration_id')) {
            $registration = Registration::find($request->registration_id);

            if ($registration) {
                $registration->delete();
                RegistrationDeleted::dispatch($request->registration_id);
            }
        }

        // audit creation of patient
        event(new ModelAction(AuditAction::CREATE, $user, $patient, null, $request));

        // returns the patient
        $patient['physician'] = $physician;
        $patient['follow_up_date'] = null;
        $patient['last_visit'] = null;
        if ($patient->photo) {
            $patient['photo_url'] = Storage::temporaryUrl($patient->photo, now()->addMinutes(45));
        }
        $patient['is_new_in_department'] = $patient->isNewInDepartment($user->department_id);
        return $patient;
    }

    public function show(Request $request, Patient $patient)
    {
        $user = $request->user();

        $this->getPatientAdditionalInformation($patient, $user);
        $patient['physician'] = $patient->getPhysician($user->department_id);

        // implements audit of patient retrieval
        event(new ModelAction(AuditAction::RETRIEVE, $request->user(), $patient, null, $request));

        return $patient;
    }

    public function update(Request $request, Patient $patient)
    {
        $user = $request->user();

        $fields = $request->validate([
            'first_name' => 'string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name'=> 'string|max:100',
            'suffix' => 'nullable|string|max:20',
            'birthdate' => 'date|date_format:Y-m-d',
            'birthplace' => 'string',
            'sex' => 'string|max:6',
            'house_number' => 'string|max:50',
            'street' => 'nullable|string|max:20',
            'barangay' => 'string|max:50',
            // 'barangay_code' => 'string|max:10',
            'city' => 'string|max:50',
            'city_code' => 'string|max:10',
            'province' => 'nullable|string|max:50',
            'province_code' => 'nullable|string|max:10',
            'region' => 'string|max:50',
            'region_code' => 'string|max:10',
            'postal_code' => 'nullable|int|digits_between:1,4',
            'civil_status' => 'string|max:20',
            'religion' => 'nullable|string|max:100',
            'phone_number' => 'string|size:10',
            'email' => 'nullable|email|max:100',
            'payment_method' => 'string|in:Cash,HMO',
            'hmo' => 'nullable|required_if:payment_method,HMO|string'
        ]);

        $request->validate([
            'photo' => 'image|mimes:png|max:2048|dimensions:min_width=200,min_height=200',
            'physician_id' => 'int|exists:users,id'
        ]);

        if ($request->filled('physician_id')) {
            $physician = User::where('id', $request->physician_id)
                        ->whereBlind('role', 'role_index', 'physician')
                        ->select('id', 'full_name', 'first_name', 'middle_name', 'last_name', 'suffix')
                        ->first();

            if (!$physician) {
                return response()->json([
                    'message' => 'Physician not found.'
                ], 400);
            } else {
                PatientPhysician::where('patient_id', $patient->id)->where('department_id', $user->department_id)->delete();
                $patient->physicians()->syncWithoutDetaching([$request->physician_id => ['department_id' => $user->department_id]]);
            }
        }

        if ($request->filled('first_name') || $request->filled('last_name'))
        {
            $patient->update(['full_name' => "{$request->first_name} {$request->last_name}"]);
        }

        $photo = $request->file('photo');
        if ($photo) {

            $imageBytes = file_get_contents($photo->getRealPath());
            if(!$this->isQualityIdentificationPhoto($imageBytes, $this->rekognitionService)) {
                return response()->json([
                    'message' => 'Invalid Photo'
                ], 400);
            }

            if ($patient->photo != null && $patient->photo != "") {
                Storage::delete($patient->photo);
            }
            $path = $photo->store('images/patients', ['local', 'private']);
            $patient->update(['photo' => $path]);
            
            // delete previous indexed face and index the new face to rekognition collection
            if($patient->rekognitionFaceId != null) {
                $this->rekognitionService->deleteFaceFromCollection($patient->rekognitionFaceId, 'patients');
            }
            $result = $this->rekognitionService->indexFaces($imageBytes, $patient->patient_number, 'patients');
            if($result['success'] == true) {
                if(count($result['result']) > 0) {
                    $patient->update(['rekognitionFaceId' => $result['result']['Face']['FaceId']]);
                }
            }
        }

        $patient->makeHidden(['qr_status', 'age', 'address', 'vital_signs', 'full_name']);

        $originalData = $patient->toArray();

        $patient->update($fields);

        // implements audit of update
        event(new ModelAction(AuditAction::UPDATE, $request->user(), $patient, $originalData, $request));

        $patient->makeVisible(['qr_status', 'age', 'address', 'vital_signs', 'full_name']);
        if ($patient->photo) {
            $patient['photo_url'] = Storage::temporaryUrl($patient->photo, now()->addMinutes(45));
        }
        $patient['physician'] = $patient->getPhysician($user->department_id);
        $patient['follow_up_date'] = $patient->getFollowUpDate($user->department_id);
        $patient['last_visit'] = $patient->getLastVisitDate($user->department_id);
        $patient['is_new_in_department'] = $patient->isNewInDepartment($user->department_id);

        return $patient;
    }

    public function getUsingId(Request $request) {
        $request->validate([
            'patient_number' => 'required|string|size:11'
        ]);

        $patient = Patient::whereBlind('patient_number', 'patient_number_index', $request->patient_number)->first();
        if (!$patient) {
            return response()->json(['message' => 'Patient not found.'], 404);
        }
        Gate::authorize('is-assigned-physician', [$patient->id]);

        $user = $request->user();

        $this->getPatientAdditionalInformation($patient, $user);
        $patient['consultations'] = $patient->consultations()->orderBy('created_at', 'desc')->get();

        // implements audit of patient retrieval
        event(new ModelAction(AuditAction::RETRIEVE, $user, $patient, null, $request));

        return $patient;
    }

    public function getUsingQr(Request $request) 
    {
        $user = $request->user();

        $request->validate([
            'qr_code' => 'required|string|size:43'
        ]);

        $code = strtolower($request->qr_code);
        $patientQr = PatientQr::whereBlind('uuid', 'uuid_index', $code)
                                ->where('is_deactivated', 0)
                                ->where('created_at', '>', now()->subYear())
                                ->latest()->first();

        if($patientQr) {
            $patient = $patientQr->patient;
            $this->getPatientAdditionalInformation($patient, $user);
            if ($request->user()->role === "physician") {
                Gate::authorize('is-assigned-physician', [$patient->id]);
                $patient['consultations'] = $patient->consultations()->orderBy('created_at', 'desc')->get();
            } else {
                $patient['physician'] = $patient->getPhysician($user->department_id);
            }

            // implements audit of patient retrieval
            event(new ModelAction(AuditAction::RETRIEVE, $user, $patient, null, $request));

            return $patient;
        }

        return response()->json([
            'message' => 'QR code not found.'
        ], 404);
    }

    public function getUsingFace(Request $request) 
    {
        $user = $request->user();

        $request->validate([
            'photo' => 'required|image|mimes:jpg,png|max:2048|dimensions:min_width=640,min_height=480'
        ]);

        $imageBytes = file_get_contents($request->file('photo')->getRealPath());
        $result = $this->rekognitionService->searchFacesByImage($imageBytes, 'patients');

        if($result['success'] == false || count($result['result']) < 1) {
            return response()->json([
                'message' => 'Patient not found.'
            ], 404);
        }

        $patientFaceId = $result['result']['Face']['FaceId'];
        $patient = Patient::where('rekognitionFaceId', $patientFaceId)->first();

        if($patient == null) {
            return response()->json([
                'message' => 'Patient not found.'
            ], 404);
        }

        $this->getPatientAdditionalInformation($patient, $user);
        if ($user->role === "physician") {
            Gate::authorize('is-assigned-physician', [$patient->id]);
            $patient['consultations'] = $patient->consultations()->orderBy('created_at', 'desc')->get();
        } else {
            $patient['physician'] = $patient->getPhysician($user->department_id);
        }

        // implements audit of patient retrieval
        event(new ModelAction(AuditAction::RETRIEVE, $user, $patient, null, $request));

        return $patient;
    }

    public function verifyPatientUsingFace(Request $request) {
        $request->validate([
            'patient_id' => 'required|numeric',
            'photo' => 'required|image|mimes:jpg,png|max:2048|dimensions:min_width=640,min_height=480',
        ]);

        $patient = Patient::select('photo')->where('id', $request->patient_id)->first();

        if(!$patient) {
            return response()->json([
                'message' => 'Patient not found.'
            ], 404);
        }

        if(!$patient->photo) {
            return response()->json([
                'message' => 'Photo not available.'
            ], 400);
        }
        
        $sourceImageBytes = Storage::get($patient->photo);
        $targetImageBytes = file_get_contents($request->file('photo')->getRealPath());

        $result = $this->rekognitionService->compareFaces($sourceImageBytes, $targetImageBytes);

        if($result['success'] ==  false) {
            return response()->json([], status: 500);
        }

        return response()->json([
            'success' => count($result['result']) > 0 ? true : false,
        ]);
    }

    public function getDuplicates(Request $request) 
    {
        $user = $request->user();

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'=> 'required|string|max:100',
            'birthdate' => 'required|date|date_format:Y-m-d',
            'sex' => 'required|string|max:6'
        ]);

        $patients = Patient::select('id', 'patient_number', 'first_name', 'middle_name', 'last_name', 'suffix', 'full_name', 'birthdate', 'sex', 'photo', 'created_at')
                            ->whereBlind('first_name', 'first_name_index', $request->first_name)
                            ->whereBlind('last_name', 'last_name_index', $request->last_name)
                            ->whereBlind('birthdate', 'birthdate_index', $request->birthdate)
                            ->whereBlind('sex', 'sex_index', $request->sex)
                            ->get();

        foreach ($patients as $patient) {
            if ($patient->photo && Storage::exists($patient->photo)) {
                $patient['photo_url'] = Storage::temporaryUrl($patient->photo, now()->addMinutes(45));
            }
        }

        return $patients;
    }

    // public function destroy(Patient $patient)
    // {
    //     $dateThreshold = now()->subYears(10);
    //     $lastConsultationRecordDate = $patient->consultationRecords()
    //                                         ->select('created_at')
    //                                         ->orderBy('created_at','desc')
    //                                         ->pluck('created_at')
    //                                         ->first();

    //     //if patient or patient's last consultation date is not past 10 years, patient cannot be deleted
    //     if ($patient->updated_at >= $dateThreshold || $lastConsultationRecordDate >= $dateThreshold) 
    //     {
    //         return response()->json([
    //             'message' => 'Patient record cannot be deleted 10 years before last record.'
    //         ], 403);
    //     }

    //     if ($patient->photo)
    //     {
    //         Storage::delete($patient->photo);
    //     }

    //     $patient->delete();

    //     return response()->json([
    //         'message' => 'Patient successfully deleted.'
    //     ], 200);
    // }

    // public function getPhoto(Patient $patient)
    // {
    //     $path = $patient->photo;

    //     if ($path != null) {
    //         if (!Storage::exists($path)) {
    //             return response()->json(['error' => 'Photo not found'], 404);
    //         }
    
    //         $mimeType = Storage::mimeType($path);
    
    //         return response(Storage::get($path))->headers('Content-Type', $mimeType);
    //     }

    //     return response()->json([
    //         'message' => 'Patient has no photo.'
    //     ], 400);
    // }

    // public function updatePhoto(Request $request, Patient $patient) {
    //     $request->validate([
    //         'image' => 'required|image|mimes:png|max:2048|dimensions:min_width=200,min_height=200'
    //     ]);
        
    //     if($patient->photo != null) {
    //         Storage::delete($patient->photo);;
    //     }

    //     $path = $request->file('image')->store('images/patients', ['local', 'private']);
    //     $patient->update(['photo' => $path]);

    //     $mimeType = Storage::mimeType($path);

    //     return response(Storage::get($path))->header('Content-Type', $mimeType);
    // }
}
