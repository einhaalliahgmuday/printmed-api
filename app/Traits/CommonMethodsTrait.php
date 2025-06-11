<?php 

namespace App\Traits;

use App\Models\Patient;
use App\Models\ResetToken;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\AmazonRekognitionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait CommonMethodsTrait
{
    private function getFullName($firstName, $middleName, $lastName, $suffix): string
    {
        $fullName = "{$firstName}";

        if ($middleName) {
            $fullName .= " " . strtoupper($middleName[0]) . '.';
        }

        $fullName .= " {$lastName}";

        if ($suffix) {
            $fullName .= " {$suffix}";
        }

        return $fullName;
    }

    public function isUserEmailExists(string $email)
    {
        return User::whereBlind('email', 'email_index', $email)->exists();
    }

    public function isUserPersonnelNumberExists(string $personnelNumber)
    {
        return User::whereBlind('personnel_number', 'personnel_number_index', $personnelNumber)->exists();
    }

    public function sendResetLink(bool $isNewAccount, User $user) 
    {
        $token = Str::random(60);

        ResetToken::create([
            'email' => $user->email,
            'token' => Hash::make($token),
            'expires_at' => now()->addHours(24)
        ]);
        
        $user->notify(new ResetPasswordNotification($isNewAccount, $token, $user->email)); //$user->notify
    }

    public function getPatientAdditionalInformation(Patient $patient, User $user) {
        if($patient->photo) {
            $patient['photo_url'] = Storage::temporaryUrl($patient->photo, now()->addMinutes(45));
        }
        $patient['follow_up_date'] = $patient->getFollowUpDate($user->department_id);
        $patient['last_visit'] = $patient->getLastVisitDate($user->department_id);
        $patient['is_new_in_department'] = $patient->isNewInDepartment($user->department_id);

        // return $patient;
    }

    public function isQualityIdentificationPhoto($imageBytes, AmazonRekognitionService $rekognitionService): bool {
        $result = $rekognitionService->detectFaces($imageBytes);

        if(count($result) === 1) {
            $face = $result[0];

            if($face != null && $face['Confidence'] > 98 && $face['Pose']['Roll'] >= -15 && $face['Pose']['Roll'] <= 15
                && $face['Pose']['Yaw'] >= -15 && $face['Pose']['Yaw'] <= 15 && $face['Pose']['Pitch'] >= -15 && $face['Pose']['Pitch'] <= 15
                && $face['BoundingBox']['Height'] > 0.4 && $face['FaceOccluded']['Value'] == false && $face['Sunglasses']['Value'] == false
                && $face['Eyeglasses']['Value'] == false) {
                return true;
            }
            return false;
        }
        return false;
    }

    public function getPrescriptionsPages($prescriptions) {
        $cuts = [];

        $pageLineCount = 0;
        $page = 1;

        foreach($prescriptions as $index => $prescription) {
            $instructionLength = strlen($prescription->instruction);
            $instructionLineCount = 0;

            if ($instructionLength <= 45) {
                $instructionLineCount = 1;
            } else if ($instructionLength > 45 && $instructionLength <= 90) {
                $instructionLineCount = 2;
            } else if ($instructionLength > 90 && $instructionLength <= 135) {
                $instructionLineCount = 3;
            } else if ($instructionLength > 135 && $instructionLength <= 180) {
                $instructionLineCount = 4;
            } else if ($instructionLength > 180 && $instructionLength <= 225) {
                $instructionLineCount = 5;
            } else if ($instructionLength > 225) {
                $instructionLineCount = 6;
            }

            $prescriptionLineCount = $index ==  0 ? $instructionLineCount + 1 : $instructionLineCount + 2;

            if (($prescriptionLineCount + $pageLineCount) > 17) {
                $page++;
                $pageLineCount = 0;
            }

            $pageLineCount += $prescriptionLineCount;
            $cuts[$page][] = $prescription;
        }

        return $cuts;
    }
}