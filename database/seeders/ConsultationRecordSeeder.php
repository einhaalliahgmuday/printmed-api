<?php

namespace Database\Seeders;

use App\Models\ConsultationRecord;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class ConsultationRecordSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();

        foreach ($patients as $patient) {
            ConsultationRecord::create([
                'height' => 170.5,
                'weight' => 70.0,
                'blood_pressure' => '120/80',
                'temperature' => 98.6,
                'chief_complaint' => 'Regular check-up',
                'history_of_present_illness' => 'No significant history',
                'family_hx' => 'No hereditary diseases',
                'medical_hx' => 'Allergies to peanuts',
                'pediatrics_h' => null,
                'pediatrics_e' => null,
                'pediatrics_a' => null,
                'pediatrics_d' => null,
                'primary_diagnosis' => null,
                'diagnosis' => 'Healthy',
                'prescription' => 'Multivitamins',
                'follow_up_date' => null,
                'patient_id' => $patient->id,
                'physician_id' => 2,
                'department_id' => 1
            ]);
        }
    }
}
