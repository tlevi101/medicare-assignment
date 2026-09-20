<?php

namespace Database\Factories;

use App\Models\Availability;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
#[UseModel(Availability::class)]
class AvailabilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays($this->faker->numberBetween(1, 30))
            ->startOfHour();
        $slot = $this->faker->randomElement([30, 60]);

        return [
            'doctor_id' => Doctor::factory(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addMinutes($slot * $this->faker->numberBetween(1, 8)),
            'slot' => $slot,
        ];
    }
}
