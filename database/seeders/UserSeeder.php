<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'role' => 'admin',
            'personnel_number' => 'PN000001',
            'first_name' => 'Einha Alliah',
            'middle_name' => 'Genciana',
            'last_name' => 'Muday',
            'sex' => 'Female',
            'birthdate' => '2003-02-19',
            'email' => 'einhalliahmuday@gmail.com',
            'password' => 'password',
        ]);
    }
}
