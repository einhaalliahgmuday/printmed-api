<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            [
                'patient_number' => 'PN000001',
                'full_name' => 'Samantha Joy',
                'first_name' => 'Samantha',
                'last_name' => 'Joy',
                'birthdate' => '1990-01-01',
                'sex' => 'Female',
                'civil_status' => 'Single',
                'religion' => 'Christianity',
                'phone_number' => '123-456-7890',
            ],
            [
                'patient_number' => 'PN000002',
                'full_name' => 'James Smith',
                'first_name' => 'James',
                'last_name' => 'Smith',
                'birthdate' => '1985-05-15',
                'sex' => 'Male',
                'civil_status' => 'Married',
                'religion' => 'Judaism',
                'phone_number' => '987-654-3210'
            ],
            [
                'patient_number' => 'PN000003',
                'full_name' => 'Emily Johnson',
                'first_name' => 'Emily',
                'last_name' => 'Johnson',
                'birthdate' => '1992-11-30',
                'sex' => 'Female',
                'civil_status' => 'Divorced',
                'religion' => 'Islam',
                'phone_number' => '555-123-4567'
            ]
        ];

        foreach ($patients as $patient) {
            Patient::create($patient);
        }
    }
}
