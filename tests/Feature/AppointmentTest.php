<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;

/**
 * Builds a payload for booking an appointment based on the given availability.
 *
 * @param  int  $offsetInMinutes  Distance of the requested slot from the start of the availability.
 * @param  int|null  $lengthInMinutes  Defaults to the availability's slot.
 * @return array{doctor_id: int, starts_at: string, ends_at: string}
 */
function bookingPayload(
    Availability $availability,
    int $offsetInMinutes = 0,
    ?int $lengthInMinutes = null,
): array {
    $lengthInMinutes ??= $availability->slot;

    return [
        'doctor_id' => $availability->doctor_id,
        'starts_at' => (clone $availability->starts_at)->addMinutes($offsetInMinutes)->toIso8601String(),
        'ends_at' => (clone $availability->starts_at)->addMinutes($offsetInMinutes + $lengthInMinutes)->toIso8601String(),
    ];
}

beforeEach(function () {
    $this->patient = Patient::factory()->create();
    $this->doctor = Doctor::factory()->create();

    $this->startsAt = now()->addDay()->startOfHour();
    $this->availability = Availability::factory()->create([
        'doctor_id' => $this->doctor->id,
        'starts_at' => $this->startsAt,
        'ends_at' => (clone $this->startsAt)->addHours(4),
        'slot' => 30,
    ]);
});

describe('appointments index', function () {
    it('lists the appointments of the patient', function () {
        Appointment::factory()->count(3)->create(['patient_id' => $this->patient->id]);

        $response = $this->getJson("/api/patients/{$this->patient->id}/appointments");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('only lists the appointments of the given patient', function () {
        Appointment::factory()->count(2)->create(['patient_id' => $this->patient->id]);
        Appointment::factory()->count(3)->create();

        $response = $this->getJson("/api/patients/{$this->patient->id}/appointments");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('filters by status', function () {
        Appointment::factory()->count(2)->create(['patient_id' => $this->patient->id]);
        Appointment::factory()->confirmed()->count(3)->create(['patient_id' => $this->patient->id]);

        $response = $this->getJson(
            "/api/patients/{$this->patient->id}/appointments?filter[status]=confirmed"
        );

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.attributes.status', AppointmentStatus::Confirmed->value);
    });

    it('returns every status without a filter', function () {
        Appointment::factory()->count(2)->create(['patient_id' => $this->patient->id]);
        Appointment::factory()->confirmed()->count(3)->create(['patient_id' => $this->patient->id]);

        $response = $this->getJson("/api/patients/{$this->patient->id}/appointments");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    });

    it('rejects an unknown status', function () {
        $response = $this->getJson(
            "/api/patients/{$this->patient->id}/appointments?filter[status]=nope"
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['filter.status']);
    });
});
describe('appointments show', function () {
    it('returns the appointment of the related patient', function () {
        $appointment = Appointment::factory()->create(['patient_id' => $this->patient->id]);

        $response = $this->getJson("/api/patients/{$this->patient->id}/appointments/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => (string) $appointment->id,
                    'type' => 'appointments',
                    'attributes' => [
                        'status' => AppointmentStatus::Pending->value,
                    ],
                ],
            ]);
    });

    it('fails when the appointment does not belong to the patient', function () {
        $appointment = Appointment::factory()->create();

        $response = $this->getJson("/api/patients/{$this->patient->id}/appointments/{$appointment->id}");

        $response->assertStatus(404);
    });
});

describe('appointment store: ', function () {
    it('creates an appointment with a pending status', function () {
        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability)
        );

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'type' => 'appointments',
                    'attributes' => [
                        'status' => AppointmentStatus::Pending->value,
                        'cancel_reason' => null,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'starts_at',
                        'ends_at',
                        'status',
                        'cancel_reason',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'starts_at' => $this->startsAt->toDateTimeString(),
            'ends_at' => (clone $this->startsAt)->addMinutes(30)->toDateTimeString(),
            'status' => AppointmentStatus::Pending->value,
        ]);
    });

    it('requires doctor_id, starts_at and ends_at', function () {
        $response = $this->postJson("/api/patients/{$this->patient->id}/appointments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['doctor_id', 'starts_at', 'ends_at']);
    });

    it('rejects an unknown doctor', function () {
        $response = $this->postJson("/api/patients/{$this->patient->id}/appointments", [
            ...bookingPayload($this->availability),
            'doctor_id' => $this->doctor->id + 999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['doctor_id']);
    });

    it('rejects a starts_at in the past', function () {
        $response = $this->postJson("/api/patients/{$this->patient->id}/appointments", [
            'doctor_id' => $this->doctor->id,
            'starts_at' => now()->subDay()->startOfHour()->toIso8601String(),
            'ends_at' => now()->subDay()->startOfHour()->addMinutes(30)->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at']);
    });

    it('rejects ends_at before starts_at', function () {
        $response = $this->postJson("/api/patients/{$this->patient->id}/appointments", [
            'doctor_id' => $this->doctor->id,
            'starts_at' => (clone $this->startsAt)->addMinutes(30)->toIso8601String(),
            'ends_at' => $this->startsAt->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ends_at']);
    });

    it('truncates the seconds of the requested period', function () {
        $response = $this->postJson("/api/patients/{$this->patient->id}/appointments", [
            'doctor_id' => $this->doctor->id,
            'starts_at' => (clone $this->startsAt)->addSeconds(45)->toIso8601String(),
            'ends_at' => (clone $this->startsAt)->addMinutes(30)->addSeconds(59)->toIso8601String(),
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('appointments', [
            'starts_at' => $this->startsAt->toDateTimeString(),
            'ends_at' => (clone $this->startsAt)->addMinutes(30)->toDateTimeString(),
        ]);
    });

    it('rejects a period outside every availability of the doctor', function () {
        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability, offsetInMinutes: 5 * 60)
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    });

    it('rejects a period that is not aligned to the slot grid', function () {
        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability, offsetInMinutes: 7)
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    });

    it('rejects a period longer than the slot', function () {
        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability, lengthInMinutes: 60)
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    });

    it('rejects a slot that is already booked', function () {
        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addMinutes(30),
        ]);

        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability)
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    });

    it('allows booking the slot next to a booked one', function () {
        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addMinutes(30),
        ]);

        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability, offsetInMinutes: 30)
        );

        $response->assertStatus(201);
    });

    it('allows booking a slot that another doctor has booked', function () {
        $otherDoctor = Doctor::factory()->create();
        Availability::factory()->create([
            'doctor_id' => $otherDoctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addHours(4),
            'slot' => 30,
        ]);
        Appointment::factory()->create([
            'doctor_id' => $otherDoctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addMinutes(30),
        ]);

        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability)
        );

        $response->assertStatus(201);
    });

    it('allows booking a slot that was freed by a cancelled appointment', function () {
        Appointment::factory()->cancelled()->create([
            'doctor_id' => $this->doctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addMinutes(30),
        ]);

        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability)
        );

        $response->assertStatus(201);
    });

    it('rejects a period when the patient is already booked with another doctor', function () {
        $otherDoctor = Doctor::factory()->create();
        Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $otherDoctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addMinutes(30),
        ]);

        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability)
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'starts_at' => 'The patient already has an appointment in this period.',
                'ends_at' => 'The patient already has an appointment in this period.',
            ]);
    });

    it('allows a period when the conflicting appointment of the patient was cancelled', function () {
        $otherDoctor = Doctor::factory()->create();
        Appointment::factory()->cancelled()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $otherDoctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addMinutes(30),
        ]);

        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability)
        );

        $response->assertStatus(201);
    });

    it('allows a period when another patient is booked at the same time', function () {
        $otherDoctor = Doctor::factory()->create();
        Appointment::factory()->create([
            'doctor_id' => $otherDoctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addMinutes(30),
        ]);

        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability)
        );

        $response->assertStatus(201);
    });

    it('does not free a slot of a completed appointment', function () {
        Appointment::factory()->completed()->create([
            'doctor_id' => $this->doctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addMinutes(30),
        ]);

        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments",
            bookingPayload($this->availability)
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    });
});
