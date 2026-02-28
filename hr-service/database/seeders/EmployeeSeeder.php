<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        Employee::factory()->usa()->count(5)->create();
        Employee::factory()->germany()->count(5)->create();
    }
}
