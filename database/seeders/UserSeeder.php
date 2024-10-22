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

        User::create([
            'role' => 'physician',
            'personnel_number' => 'PN000002',
            'first_name' => 'Salma Fae',
            'last_name' => 'Lumaoang',
            'sex' => 'Female',
            'birthdate' => '2003-08-09',
            'email' => 'salmafaelumaoang@gmail.com',
            'password' => 'password',
            'department_id' => 1
        ]);

        User::create([
            'role' => 'secretary',
            'personnel_number' => 'PN000003',
            'first_name' => 'Michi',
            'last_name' => 'Legaspino',
            'sex' => 'Female',
            'birthdate' => '2002-01-07',
            'email' => 'michilegaspino@gmail.com',
            'password' => 'password',
            'department_id' => 1
        ]);

        User::create([
            'role' => 'queue manager',
            'personnel_number' => 'PN000004',
            'first_name' => 'Nico',
            'last_name' => 'Ampoloquio',
            'sex' => 'Male',
            'birthdate' => '2003-01-08',
            'email' => 'nicoampoloquio@gmail.com',
            'password' => 'password',
        ]);
    }
}
