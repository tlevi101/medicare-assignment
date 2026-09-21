<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;

/**
 * Creates a new appointment with the given status.
 * @param int $startsInHours diff in hours between the appointment and now()
 */
function appointmentIn(
    Patient $patient,
    Doctor $doctor,
    AppointmentStatus $status,
    int $startsInHours,
): Appointment {
    return Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => $status,
        'starts_at' => now()->addHours($startsInHours),
        'ends_at' => now()->addHours($startsInHours)->addMinutes(30),
    ]);
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

    $this->appointment = $this->patient->appointments()->create([
        'doctor_id' => $this->doctor->id,
        'starts_at' =>  $this->startsAt,
        'ends_at' => (clone $this->startsAt)->addMinutes($this->availability->slot),
    ]);

    $this->otherPatient = \App\Models\Patient::factory()->create();
    $this->otherAppointment = $this->otherPatient->appointments()->create([
        'doctor_id' => $this->doctor->id,
        'starts_at' =>  $this->startsAt,
        'ends_at' => (clone $this->startsAt)->addMinutes($this->availability->slot * 2),
    ]);

});


describe('route params are scoped', function () {
    it('scopes for cancel', function () {
        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments/{$this->otherAppointment->id}/cancel",
            ["cancel_reason" => "Foo bar"]
        );

        $response->assertStatus(404);
    });

    it('scopes for confirm', function () {
        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments/{$this->otherAppointment->id}/confirm",
            ["cancel_reason" => "Foo bar"]
        );

        $response->assertStatus(404);
    });

    it('scopes for complete', function () {
        $response = $this->postJson(
            "/api/patients/{$this->patient->id}/appointments/{$this->otherAppointment->id}/complete",
            ["cancel_reason" => "Foo bar"]
        );

        $response->assertStatus(404);
    });
});

describe('confirm', function () {
    it('moves a pending appointment to confirmed', function () {
        $appointment = appointmentIn($this->patient, $this->doctor, AppointmentStatus::Pending, 48);

        $this->postJson("/api/patients/{$this->patient->id}/appointments/{$appointment->id}/confirm")
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.status', AppointmentStatus::Confirmed->value);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Confirmed->value,
        ]);
    });

    it('rejects an appointment that is not pending', function (AppointmentStatus $status) {
        $appointment = appointmentIn($this->patient, $this->doctor, $status, 48);

        $this->postJson("/api/patients/{$this->patient->id}/appointments/{$appointment->id}/confirm")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => $status->value,
        ]);
    })->with([
        'already confirmed' => AppointmentStatus::Confirmed,
        'completed' => AppointmentStatus::Completed,
        'cancelled' => AppointmentStatus::Cancelled,
    ]);
});

describe('complete', function () {
    it('moves a confirmed appointment to completed', function () {
        $appointment = appointmentIn($this->patient, $this->doctor, AppointmentStatus::Confirmed, 48);

        $this->postJson("/api/patients/{$this->patient->id}/appointments/{$appointment->id}/complete")
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.status', AppointmentStatus::Completed->value);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Completed->value,
        ]);
    });

    it('rejects an appointment that is not confirmed', function (AppointmentStatus $status) {
        $appointment = appointmentIn($this->patient, $this->doctor, $status, 48);

        $this->postJson("/api/patients/{$this->patient->id}/appointments/{$appointment->id}/complete")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    })->with([
        'still pending' => AppointmentStatus::Pending,
        'already completed' => AppointmentStatus::Completed,
        'cancelled' => AppointmentStatus::Cancelled,
    ]);
});

describe('cancel', function () {
    it('moves a pending appointment to cancelled and stores the reason', function () {
        $appointment = appointmentIn($this->patient, $this->doctor, AppointmentStatus::Pending, 48);

        $this->postJson(
            "/api/patients/{$this->patient->id}/appointments/{$appointment->id}/cancel",
            ['cancel_reason' => 'Foo Bar']
        )
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.status', AppointmentStatus::Cancelled->value)
            ->assertJsonPath('data.attributes.cancel_reason', 'Foo Bar');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Cancelled->value,
            'cancel_reason' => 'Foo Bar',
        ]);
    });

    it('cancels a pending appointment even within 24 hours', function () {
        $appointment = appointmentIn($this->patient, $this->doctor, AppointmentStatus::Pending, 2);

        $this->postJson(
            "/api/patients/{$this->patient->id}/appointments/{$appointment->id}/cancel",
            ['cancel_reason' => 'Foo Bar']
        )->assertStatus(200);
    });

    it('cancels a confirmed appointment more than 24 hours before it starts', function () {
        $appointment = appointmentIn($this->patient, $this->doctor, AppointmentStatus::Confirmed, 25);

        $this->postJson(
            "/api/patients/{$this->patient->id}/appointments/{$appointment->id}/cancel",
            ['cancel_reason' => 'Foo Bar']
        )->assertStatus(200);
    });

    it('rejects cancelling a confirmed appointment within 24 hours', function () {
        $appointment = appointmentIn($this->patient, $this->doctor, AppointmentStatus::Confirmed, 23);

        $this->postJson(
            "/api/patients/{$this->patient->id}/appointments/{$appointment->id}/cancel",
            ['cancel_reason' => 'foo bar']
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Confirmed->value,
            'cancel_reason' => null,
        ]);
    });

    it('rejects an appointment in a final status', function (AppointmentStatus $status) {
        $appointment = appointmentIn($this->patient, $this->doctor, $status, 48);

        $this->postJson(
            "/api/patients/{$this->patient->id}/appointments/{$appointment->id}/cancel",
            ['cancel_reason' => 'foo bar']
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    })->with([
        'completed' => AppointmentStatus::Completed,
        'already cancelled' => AppointmentStatus::Cancelled,
    ]);

    it('requires a cancel reason', function () {
        $appointment = appointmentIn($this->patient, $this->doctor, AppointmentStatus::Pending, 48);

        $this->postJson("/api/patients/{$this->patient->id}/appointments/{$appointment->id}/cancel")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cancel_reason']);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Pending->value,
        ]);
    });

    it('rejects a cancel reason that does not fit the column', function () {
        $appointment = appointmentIn($this->patient, $this->doctor, AppointmentStatus::Pending, 48);

        $this->postJson(
            "/api/patients/{$this->patient->id}/appointments/{$appointment->id}/cancel",
            ['cancel_reason' => str_repeat('a', 501)]
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cancel_reason']);
    });
});

describe('status activity log', function () {
    it('records the previous and the new status', function () {
        $appointment = appointmentIn($this->patient, $this->doctor, AppointmentStatus::Pending, 48);

        $this->postJson("/api/patients/{$this->patient->id}/appointments/{$appointment->id}/confirm")
            ->assertStatus(200);

        $this->assertDatabaseHas('appointment_status_activities', [
            'appointment_id' => $appointment->id,
            'previous' => AppointmentStatus::Pending->value,
            'new' => AppointmentStatus::Confirmed->value,
        ]);
    });

    it('records every step', function () {
        $appointment = appointmentIn($this->patient, $this->doctor, AppointmentStatus::Pending, 48);
        $url = "/api/patients/{$this->patient->id}/appointments/{$appointment->id}";

        $this->postJson("{$url}/confirm")->assertStatus(200);
        $this->postJson("{$url}/complete")->assertStatus(200);

        expect($appointment->statusActivities()->orderBy('id')->get()
            ->map(fn ($activity) => $activity->previous->value.'->'.$activity->new->value)
            ->all())
            ->toBe(['pending->confirmed', 'confirmed->completed']);
    });
});
