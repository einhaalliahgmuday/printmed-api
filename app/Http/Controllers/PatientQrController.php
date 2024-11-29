<?php

namespace App\Http\Controllers;

use App\Mail\PatientIdCard;
use App\Models\Patient;
use App\Models\PatientQr;
use Barryvdh\Snappy\Facades\SnappyImage;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PatientQrController extends Controller
{
    public function store(Request $request, Patient $patient) {
        $request->validate([
            'send_email' => 'boolean'
        ]);

        PatientQr::where('patient_id', $patient->id)->where('is_deactivated', 0)->update(['is_deactivated' => 1]);

        if (!$patient->photo) {
            return response()->json([
                'message' => "Patient's photo is required to generate identification card."
            ], 400);
        }

        if (!Storage::exists($patient->photo)) {
            return response()->json(['message' => "Patient's photo not found"], 400);
        }

        $uuid = (string) Str::uuid();
        $latestPatientQr = PatientQr::select('id')->latest()->first();
        $id = $latestPatientQr ? $latestPatientQr->id : 0;
        $uuid .= "-" . substr(str_pad($id, 6, '0', STR_PAD_LEFT), 0, 6);

        $photoPath = $patient->photo;
        $photo = Storage::get($photoPath);
        $photoBytes = base64_encode($photo);

        $qr = QrCode::size(300)
                    ->style('round') //square, dot, round
                    ->eye('circle') // square, circle
                    ->format('png')
                    ->merge('/public/images/carmona_hospital_logo_3.png')
                    ->gradient(19, 147, 79, 159, 16, 8, 'vertical')
                    ->generate($uuid);
        $qrBytes = base64_encode($qr);
        $expirationDate = now()->addMonths(12);

        if ($request->filled('send_email') && $request->send_email == 1 && $patient->email)
        {
            $idImage = SnappyImage::loadView('patient_id_card', ['patient' => $patient, 'photo' => $photoBytes, 'qr' => $qrBytes, 'expirationDate' => $expirationDate->format('F j, Y'), 'isImage' => true])
                        ->setOption('quality', 100)
                        ->setOption('zoom', 5)
                        ->setOption('format', 'jpeg')
                        ->setOption('enable-local-file-access', true);

            Mail::to($patient->email)->send(new PatientIdCard($idImage->output(), $patient->first_name));
        }

        $idPdf = SnappyPdf::loadView('patient_id_card', ['patient' => $patient, 'photo' => $photoBytes,  'qr' => $qrBytes, 'expirationDate' => $expirationDate->format('F j, Y'), 'isImage' => false])
                        ->setPaper('Letter', 'portrait')
                        ->setOption('zoom', 1.3)
                        ->setOption('enable-local-file-access', true);

        PatientQr::create([
            'uuid' => $uuid,
            'patient_id' => $patient->id
        ]);

        return response($idPdf->output())->header('Content-Type', 'application/pdf');
    }

    public function deactivate(Patient $patient) {
        PatientQr::where('patient_id', $patient->id)->where('is_deactivated', 0)->update(['is_deactivated' => 1]);

        return response()->json(['message' => 'Patient identification card successfully deactivated.']);
    }

    public function getPatient(Request $request) 
    {
        $user = $request->user();

        $request->validate([
            'qr_code' => 'string|max:100'
        ]);

        $patientQr = PatientQr::whereBlind('uuid', 'uuid_index', $request->qr_code)
                                ->where('is_deactivated', 0)
                                ->where('created_at', '>', now()->subYear())
                                ->latest()->first();

        if($patientQr) {
            $patient = $patientQr->patient;

            if ($request->user()->role === "physician") {
                Gate::authorize('is-assigned-physician', [$patient->id]);
                $patient['consultations'] = $patient->consultations()->orderBy('created_at', 'desc')->get();
            } else {
                $patient['physician'] = $patient->getPhysician($user->department_id);
            }

            $patient['follow_up_date'] = $patient->getFollowUpDate($user->department_id);

            if ($patient->photo) {
                $patient['photo_url'] = Storage::temporaryUrl($patient->photo, now()->addMinutes(45));
            }

            return $patient;
        }

        return response()->json([
            'message' => 'QR code not found.'
        ], 404);
    }
}
