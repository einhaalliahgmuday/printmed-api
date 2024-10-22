<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        Patient::create([
            'patient_number' => 'P001',
            'first_name' => 'John',
            'middle_name' => 'A',
            'last_name' => 'Doe',
            'suffix' => null,
            'birthdate' => '1990-05-15',
            'sex' => 'Male',
            'address' => '123 Elm St, Springfield',
            'civil_status' => 'Single',
            'religion' => 'Christian',
            'phone_number' => '555-1234'
        ]);

        Patient::create([
            'patient_number' => 'P002',
            'first_name' => 'Jane',
            'middle_name' => 'B',
            'last_name' => 'Smith',
            'suffix' => 'Jr.',
            'birthdate' => '1985-08-25',
            'sex' => 'Female',
            'address' => '456 Oak St, Springfield',
            'civil_status' => 'Married',
            'religion' => 'Catholic',
            'phone_number' => '555-5678'
        ]);

        Patient::create([
            'patient_number' => 'P003',
            'first_name' => 'Alice',
            'middle_name' => null,
            'last_name' => 'Johnson',
            'suffix' => null,
            'birthdate' => '2000-12-01',
            'sex' => 'Female',
            'address' => '789 Pine St, Springfield',
            'civil_status' => 'Single',
            'religion' => 'Atheist',
            'phone_number' => '555-8765'
        ]);
    }
}
