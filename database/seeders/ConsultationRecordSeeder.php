<?php

namespace Database\Seeders;

use App\Models\Consultation;
use Illuminate\Database\Seeder;

class ConsultationRecordSeeder extends Seeder
{
    public function run(): void
    {
        $consultations = [
            [
                'patient_id' => 1,
                'height' => 1.75,
                'weight' => 70.5,
                'blood_pressure' => '120/80',
                'temperature' => 37.5,
                'chief_complaint' => 'Severe headache',
                'primary_diagnosis' => 'Migraine',
                'diagnosis' => 'Migraine attack',
                'follow_up_date' => '2024-10-30',
                'physician_id' => 2,
                'department_id' => 1
            ],
            [
                'patient_id' => 2,
                'height' => 1.80,
                'weight' => 85.0,
                'blood_pressure' => '130/85',
                'temperature' => 38.0,
                'chief_complaint' => 'Fever',
                'primary_diagnosis' => 'Flu',
                'diagnosis' => 'Flu infection',
                'follow_up_date' => '2024-11-05',
                'physician_id' => 2,
                'department_id' => 1
            ],
            [
                'patient_id' => 2,
                'height' => 1.65,
                'weight' => 60.0,
                'blood_pressure' => '115/75',
                'temperature' => 36.8,
                'chief_complaint' => 'Nausea',
                'primary_diagnosis' => 'Gastroenteritis',
                'diagnosis' => 'Stomach flu',
                'follow_up_date' => '2024-11-10',
                'physician_id' => 2,
                'department_id' => 1
            ]
        ];

        foreach ($consultations as $consultation) {
            Consultation::create($consultation);
        }
    }
}
