<?php

use App\Http\Requests\FreeSlotsRequest;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;

beforeEach(function () {
    $this->doctor = Doctor::factory()->create();

    $this->startsAt = now()->addDay()->startOfHour();
    Availability::factory()->create([
        'doctor_id' => $this->doctor->id,
        'starts_at' => $this->startsAt,
        'ends_at' => (clone $this->startsAt)->addHours(2),
        'slot' => 30,
    ]);
});

describe('free slots', function () {
    it('lists the bookable slots of the doctor', function () {
        $response = $this->getJson("/api/doctors/{$this->doctor->id}/free-slots");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'type',
                        'attributes' => ['doctor_id', 'starts_at', 'ends_at'],
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.type', 'slots');
    });

    it('identifies a slot by the doctor and its start', function () {
        $response = $this->getJson("/api/doctors/{$this->doctor->id}/free-slots");

        $response->assertJsonPath(
            'data.0.id',
            $this->doctor->id.':'.$this->startsAt->toIso8601String()
        );
    });

    it('omits a slot that is already booked', function () {
        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addMinutes(30),
        ]);

        $response = $this->getJson("/api/doctors/{$this->doctor->id}/free-slots");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath(
                'data.0.attributes.starts_at',
                (clone $this->startsAt)->addMinutes(30)->toJSON()
            );
    });

    it('keeps a slot whose appointment was cancelled', function () {
        Appointment::factory()->cancelled()->create([
            'doctor_id' => $this->doctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addMinutes(30),
        ]);

        $this->getJson("/api/doctors/{$this->doctor->id}/free-slots")
            ->assertStatus(200)
            ->assertJsonCount(4, 'data');
    });

    it('only lists the slots of the given doctor', function () {
        $otherDoctor = Doctor::factory()->create();
        Availability::factory()->create([
            'doctor_id' => $otherDoctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addHours(2),
            'slot' => 30,
        ]);

        $this->getJson("/api/doctors/{$this->doctor->id}/free-slots")
            ->assertStatus(200)
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.attributes.doctor_id', $this->doctor->id);
    });

    it('never offers a slot that already started', function () {
        $startedAt = now()->subHour()->startOfHour();
        Availability::factory()->create([
            'doctor_id' => $this->doctor->id,
            'starts_at' => $startedAt,
            'ends_at' => (clone $startedAt)->addHours(2),
            'slot' => 30,
        ]);

        $response = $this->getJson("/api/doctors/{$this->doctor->id}/free-slots");

        $slots = collect($response->json('data'))
            ->pluck('attributes.starts_at')
            ->map(fn (string $startsAt) => \Illuminate\Support\Carbon::parse($startsAt));

        expect($slots->every(fn ($startsAt) => $startsAt->gte(now())))->toBeTrue();
    });

    it('cuts the slots to the queried period', function () {
        $from = (clone $this->startsAt)->addMinutes(30);
        $to = (clone $this->startsAt)->addMinutes(90);

        $response = $this->getJson(
            "/api/doctors/{$this->doctor->id}/free-slots"
            ."?from={$from->toIso8601ZuluString()}&to={$to->toIso8601ZuluString()}"
        );

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('returns an empty page when the doctor has no availability', function () {
        $otherDoctor = Doctor::factory()->create();

        $this->getJson("/api/doctors/{$otherDoctor->id}/free-slots")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    });

    it('paginates the slots themselves', function () {
        $response = $this->getJson("/api/doctors/{$this->doctor->id}/free-slots?perPage=2");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2);
    });

    it('rejects a period longer than the maximum', function () {
        $from = now()->addDay()->toIso8601ZuluString();
        $to = now()->addDays(FreeSlotsRequest::MAX_PERIOD_IN_DAYS + 2)->toIso8601ZuluString();

        $this->getJson("/api/doctors/{$this->doctor->id}/free-slots?from={$from}&to={$to}")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    });

    it('rejects a to before from', function () {
        $from = now()->addDays(3)->toIso8601ZuluString();
        $to = now()->addDay()->toIso8601ZuluString();

        $this->getJson("/api/doctors/{$this->doctor->id}/free-slots?from={$from}&to={$to}")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    });

    it('rejects an invalid perPage', function () {
        $this->getJson("/api/doctors/{$this->doctor->id}/free-slots?perPage=abc")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['perPage']);
    });

    it('fails when the doctor does not exist', function () {
        $this->getJson('/api/doctors/999999/free-slots')
            ->assertStatus(404);
    });
});
