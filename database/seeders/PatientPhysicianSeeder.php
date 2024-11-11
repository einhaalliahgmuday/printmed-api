<?php

namespace Database\Seeders;

use App\Models\PatientPhysician;
use Illuminate\Database\Seeder;

class PatientPhysicianSeeder extends Seeder
{
    public function run(): void
    {
        PatientPhysician::create([
            'physician_id' => 2,
            'patient_id' => 1
        ]);

        PatientPhysician::create([
            'physician_id' => 2,
            'patient_id' => 2
        ]);
    }
}
