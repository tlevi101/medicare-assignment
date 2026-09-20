<?php

namespace Database\Seeders;

use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Doctor::factory()->count(40)
            ->has(Availability::factory()->count(1), 'availabilities')
            ->create();
        Patient::factory()->count(40)->create();
    }
}
