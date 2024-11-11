<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        Payment::create([
            'amount' => 100,
            'method' => 'hmo',
            'is_paid' => 0,
            'consultation_id' => 1,
            'patient_id' => 1,
            'physician_id' => 2,
            'department_id' => 1,
        ]);

        Payment::create([
            'amount' => 100,
            'method' => 'cash',
            'is_paid' => 1,
            'consultation_id' => 2,
            'patient_id' => 2,
            'physician_id' => 2,
            'department_id' => 3,
        ]);
    }
}
