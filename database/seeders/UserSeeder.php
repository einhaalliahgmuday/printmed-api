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
            'role' => 'super admin',
            'personnel_number' => 'PN-0000001',
            'full_name' => 'Einha Alliah Muday',
            'first_name' => 'Einha Alliah',
            'middle_name' => 'Genciana',
            'last_name' => 'Muday',
            'sex' => 'Female',
            'birthdate' => '2003-02-19',
            'email' => 'einhalliahmuday@gmail.com',
            'password' => 'Pass12**',
        ]);

        User::create([
            'role' => 'physician',
            'personnel_number' => 'PN-0000002',
            'full_name' => 'Salma Fae Lumaoang',
            'first_name' => 'Salma Fae',
            'last_name' => 'Lumaoang',
            'sex' => 'Female',
            'birthdate' => '2003-08-09',
            'email' => 'einhaalliah@gmail.com',
            'password' => 'Pass12**',
            'department_id' => 1
        ]);

        User::create([
            'role' => 'secretary',
            'personnel_number' => 'PN-0000003',
            'full_name' => 'Michi Legaspino',
            'first_name' => 'Michi',
            'last_name' => 'Legaspino',
            'sex' => 'Female',
            'birthdate' => '2002-01-07',
            'email' => 'aynamuday@gmail.com',
            'password' => 'Pass12**',
            'department_id' => 1
        ]);

        User::create([
            'role' => 'physician',
            'personnel_number' => 'PN-0000004',
            'full_name' => 'Mekaila Mae Aguila',
            'first_name' => 'Mekaila Mae',
            'last_name' => 'Aguila',
            'sex' => 'Female',
            'birthdate' => '2003-11-06',
            'email' => 'mekailamaeaguila@gmail.com',
            'password' => 'Pass12**',
            'department_id' => 2
        ]);
        
        User::create([
            'role' => 'admin',
            'personnel_number' => 'PN-0000005',
            'full_name' => 'Nico Ampoloquio',
            'first_name' => 'Nico',
            'last_name' => 'Ampoloquio',
            'sex' => 'Male',
            'birthdate' => '2003-02-10',
            'email' => 'louiejhe.store@gmail.com',
            'password' => 'Pass12**'
        ]);
    }
}
