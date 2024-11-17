<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PatientQrIdController extends Controller
{
    public function store(Patient $patient) {
        if (!$patient->photo) {
            return response()->json([
                'message' => "Patient's photo is required to generate identification card."
            ], 400);
        }

        if (!Storage::exists($patient->photo)) {
            return response()->json(['error' => "Patient's photo not found"], 400);
        }

        $uuid = (string) Str::uuid();
        $photoPath = $patient->photo;

        $photo = Storage::get($photoPath);
        $imagePath = public_path('images/patient_id_card_bg.png');

        // dd($imagePath);

        $qr = QrCode::size(300)
                    ->style('round') //square, dot, round
                    ->eye('circle') // square, circle
                    ->format('png')
                    ->merge('/public/images/carmona_hospital_logo_3.png')
                    ->gradient(19, 147, 79, 159, 16, 8, 'vertical')
                    ->generate($uuid);

        $pdf = SnappyPdf::setOption('enable-local-file-access', true)->loadView('patient_id_card', ['patient' => $patient, 'photo' => $imagePath, 'qr' => $qr])->setPaper('Letter', 'portrait');

        return response($pdf->output())->header('Content-Type', 'application/pdf');

        // return response($qr)->header('Content-Type', 'image/png');
    }

    public function deactivate(Patient $patient) {

    }
}
