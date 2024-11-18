<?php

namespace App\Http\Controllers;

use App\Mail\PatientIdCard;
use App\Models\Patient;
use App\Models\PatientQr;
use Barryvdh\Snappy\Facades\SnappyImage;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Http\Request;
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

        PatientQr::where('patient_id', $patient->id)->where('isDeactivated', 0)->update(['isDeactivated' => 1]);

        if (!$patient->photo) {
            return response()->json([
                'message' => "Patient's photo is required to generate identification card."
            ], 400);
        }

        if (!Storage::exists($patient->photo)) {
            return response()->json(['message' => "Patient's photo not found"], 400);
        }

        $uuid = (string) Str::uuid();
        $loopCount = 0;

        while (PatientQr::whereBlind('uuid', 'uuid_index', $uuid)->exists() && $loopCount < 3)
        {
            $uuid = (string) Str::uuid();
        }

        if (PatientQr::whereBlind('uuid', 'uuid_index', $uuid)->exists())
        {
            return response()->json(['message' => 'There was a problem generating an ID. Please try again.'], 500);
        }

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
        $expirationDate = now()->addMonths(10);

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
        PatientQr::where('patient_id', $patient->id)->where('isDeactivated', 0)->update(['isDeactivated' => 1]);

        return response()->json(['message' => 'Patient identification card successfully deactivated.']);
    }
}
