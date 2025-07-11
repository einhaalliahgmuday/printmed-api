<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Queue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::create(['name' => 'Pediatrics']);
        Department::create(['name' => 'Opthalmology']);
    }
}
