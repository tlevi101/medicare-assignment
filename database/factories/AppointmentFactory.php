<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
#[UseModel(Appointment::class)]
class AppointmentFactory extends Factory
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

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addMinutes(30),
            'status' => AppointmentStatus::Pending,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => AppointmentStatus::Confirmed]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => AppointmentStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => AppointmentStatus::Cancelled,
            'cancel_reason' => $this->faker->sentence(),
        ]);
    }
}
