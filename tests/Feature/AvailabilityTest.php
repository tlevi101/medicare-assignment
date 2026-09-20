<?php

beforeEach(function () {
    $this->doctor = \App\Models\Doctor::factory()->create();
});

test('availabilities index', function () {
    \App\Models\Availability::factory()->count(3)->create(['doctor_id' => $this->doctor->id]);

    $response = $this->getJson("/api/doctors/{$this->doctor->id}/availabilities");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('availabilities index only lists the given doctor availabilities', function () {
    \App\Models\Availability::factory()->count(2)->create(['doctor_id' => $this->doctor->id]);
    \App\Models\Availability::factory()->count(3)->create();

    $response = $this->getJson("/api/doctors/{$this->doctor->id}/availabilities");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

describe('availabilities show', function (){
    it('returns availability for the related doctor', function () {
        $availability = \App\Models\Availability::factory()->create(['doctor_id' => $this->doctor->id]);

        $response = $this->getJson("/api/doctors/{$this->doctor->id}/availabilities/{$availability->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $availability->id,
                    'type' => 'availabilities',
                    'attributes' => [
                        'slot' => $availability->slot,
                    ],
                ],
            ]);
    });

    it('fails when the availability does not belong to the doctor', function () {
        $availability = \App\Models\Availability::factory()->create();

        $response = $this->getJson("/api/doctors/{$this->doctor->id}/availabilities/{$availability->id}");

        $response->assertStatus(404);
    });

});

describe('availability store: ', function () {
    it('creates an availability', function () {
        $availabilityData = [
            'starts_at' => now()->addDay()->startOfHour()->toIso8601String(),
            'ends_at' => now()->addDay()->startOfHour()->addHours(4)->toIso8601String(),
            'slot' => 30,
        ];

        $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", $availabilityData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'type' => 'availabilities',
                    'attributes' => [
                        'slot' => 30,
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
                        'slot',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('availabilities', [
            'doctor_id' => $this->doctor->id,
            'slot' => 30,
        ]);
    });

    it('requires starts_at, ends_at and slot', function () {
        $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at', 'ends_at', 'slot']);
    });

    it('rejects a starts_at in the past', function () {
        $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", [
            'starts_at' => now()->subDay()->startOfHour()->toIso8601String(),
            'ends_at' => now()->subDay()->startOfHour()->addHours(2)->toIso8601String(),
            'slot' => 60,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at']);
    });

    it('rejects ends_at before starts_at', function () {
        $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", [
            'starts_at' => now()->addDay()->startOfHour()->addHours(4)->toIso8601String(),
            'ends_at' => now()->addDay()->startOfHour()->toIso8601String(),
            'slot' => 60,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    });

    it('rejects a slot below 30 minutes', function () {
        $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", [
            'starts_at' => now()->addDay()->startOfHour()->toIso8601String(),
            'ends_at' => now()->addDay()->startOfHour()->addHours(2)->toIso8601String(),
            'slot' => 15,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slot']);
    });

    it('rejects a period that is not divisible by the slot', function () {
        $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", [
            'starts_at' => now()->addDay()->startOfHour()->toIso8601String(),
            'ends_at' => now()->addDay()->startOfHour()->addMinutes(90)->toIso8601String(),
            'slot' => 60,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    });

    describe('rejects overlapping availabilities when ', function () {

        beforeEach(function () {
            $this->startsAt = now()->addDay()->startOfHour();
            \App\Models\Availability::factory()->create([
                'doctor_id' => $this->doctor->id,
                'starts_at' => $this->startsAt,
                'ends_at' => (clone $this->startsAt)->addHours(4),
                'slot' => 60,
            ]);
        });

        it('starts_at in between an existing availability', function () {
            $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", [
                'starts_at' => (clone $this->startsAt)->addHours(2)->toIso8601String(),
                'ends_at' => (clone $this->startsAt)->addHours(6)->toIso8601String(),
                'slot' => 60,
            ]);
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['starts_at', 'ends_at']);

        });

        it('ends_at in between an existing availability', function () {
            $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", [
                'starts_at' => (clone $this->startsAt)->subHours(2)->toIso8601String(),
                'ends_at' => (clone $this->startsAt)->addHours(2)->toIso8601String(),
                'slot' => 60,
            ]);
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['starts_at', 'ends_at']);
        });

        it('completely overlaps an existing availability', function () {
            $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", [
                'starts_at' => (clone $this->startsAt)->subHours(2)->toIso8601String(),
                'ends_at' => (clone $this->startsAt)->addHours(6)->toIso8601String(),
                'slot' => 60,
            ]);
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['starts_at', 'ends_at']);
        });
    });

    it('allows an availability adjacent to an existing one', function () {
        $startsAt = now()->addDay()->startOfHour();
        \App\Models\Availability::factory()->create([
            'doctor_id' => $this->doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addHours(4),
            'slot' => 60,
        ]);

        $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", [
            'starts_at' => (clone $startsAt)->addHours(4)->toIso8601String(),
            'ends_at' => (clone $startsAt)->addHours(8)->toIso8601String(),
            'slot' => 60,
        ]);

        $response->assertStatus(201);
    });

    it('allows an overlap with another doctor availability', function () {
        $startsAt = now()->addDay()->startOfHour();
        \App\Models\Availability::factory()->create([
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addHours(4),
            'slot' => 60,
        ]);

        $response = $this->postJson("/api/doctors/{$this->doctor->id}/availabilities", [
            'starts_at' => (clone $startsAt)->toIso8601String(),
            'ends_at' => (clone $startsAt)->addHours(4)->toIso8601String(),
            'slot' => 60,
        ]);

        $response->assertStatus(201);
    });
});

describe('availability update: ', function () {
    beforeEach(function () {
        $this->startsAt = now()->addDay()->startOfHour();
        $this->availability = \App\Models\Availability::factory()->create([
            'doctor_id' => $this->doctor->id,
            'starts_at' => $this->startsAt,
            'ends_at' => (clone $this->startsAt)->addHours(4),
            'slot' => 60,
        ]);
    });

    it('updates an availability', function () {
        $availabilityData = [
            'starts_at' => (clone $this->startsAt)->addDay()->toIso8601String(),
            'ends_at' => (clone $this->startsAt)->addDay()->addHours(3)->toIso8601String(),
            'slot' => 30,
        ];

        $response = $this->putJson(
            "/api/doctors/{$this->doctor->id}/availabilities/{$this->availability->id}",
            $availabilityData
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->availability->id,
                    'type' => 'availabilities',
                    'attributes' => [
                        'slot' => 30,
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
                        'slot',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('availabilities', [
            'id' => $this->availability->id,
            'slot' => 30,
        ]);
    });

    it('partially updates an availability', function () {
        $response = $this->patchJson(
            "/api/doctors/{$this->doctor->id}/availabilities/{$this->availability->id}",
            ['slot' => 120]
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'type' => 'availabilities',
                    'attributes' => [
                        'slot' => 120,
                    ],
                ],
            ]);
    });

    it('does not report the availability as overlapping itself', function () {
        $response = $this->putJson(
            "/api/doctors/{$this->doctor->id}/availabilities/{$this->availability->id}",
            [
                'starts_at' => (clone $this->startsAt)->toIso8601String(),
                'ends_at' => (clone $this->startsAt)->addHours(6)->toIso8601String(),
                'slot' => 60,
            ]
        );

        $response->assertStatus(200);
    });

    describe('rejects overlapping availabilities when ', function () {
        beforeEach(function () {
            \App\Models\Availability::factory()->create([
                'doctor_id' => $this->doctor->id,
                'starts_at' => (clone $this->startsAt)->addHours(5),
                'ends_at' => (clone $this->startsAt)->addHours(9),
                'slot' => 60,
            ]);
        });

        it('starts_at in between an existing availability', function () {
            $response = $this->putJson(
                "/api/doctors/{$this->doctor->id}/availabilities/{$this->availability->id}",
                [
                    'starts_at' => (clone $this->startsAt)->addHours(6)->toIso8601String(),
                    'ends_at' => (clone $this->startsAt)->addHours(10)->toIso8601String(),
                    'slot' => 60,
                ]
            );

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['starts_at', 'ends_at']);
        });

        it('ends_at in between an existing availability', function () {
            $response = $this->putJson(
                "/api/doctors/{$this->doctor->id}/availabilities/{$this->availability->id}",
                [
                    'starts_at' => (clone $this->startsAt)->addHours(3)->toIso8601String(),
                    'ends_at' => (clone $this->startsAt)->addHours(6)->toIso8601String(),
                    'slot' => 60,
                ]
            );

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['starts_at', 'ends_at']);
        });

        it('completely overlaps an existing availability', function () {
            $response = $this->putJson(
                "/api/doctors/{$this->doctor->id}/availabilities/{$this->availability->id}",
                [
                    'starts_at' => (clone $this->startsAt)->addHours(4)->toIso8601String(),
                    'ends_at' => (clone $this->startsAt)->addHours(10)->toIso8601String(),
                    'slot' => 60,
                ]
            );

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['starts_at', 'ends_at']);
        });
    });

    it('rejects a starts_at in the past', function () {
        $response = $this->putJson(
            "/api/doctors/{$this->doctor->id}/availabilities/{$this->availability->id}",
            [
                'starts_at' => now()->subDay()->startOfHour()->toIso8601String(),
                'ends_at' => now()->subDay()->startOfHour()->addHours(2)->toIso8601String(),
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at']);
    });

    it('rejects a slot below 30 minutes', function () {
        $response = $this->putJson(
            "/api/doctors/{$this->doctor->id}/availabilities/{$this->availability->id}",
            ['slot' => 15]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slot']);
    });

    it('rejects a period that is not divisible by the slot', function () {
        $response = $this->putJson(
            "/api/doctors/{$this->doctor->id}/availabilities/{$this->availability->id}",
            [
                'starts_at' => (clone $this->startsAt)->toIso8601String(),
                'ends_at' => (clone $this->startsAt)->addMinutes(90)->toIso8601String(),
                'slot' => 60,
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
    });

});

test('availabilities delete', function () {
    $availability = \App\Models\Availability::factory()->create(['doctor_id' => $this->doctor->id]);

    $response = $this->deleteJson("/api/doctors/{$this->doctor->id}/availabilities/{$availability->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('availabilities', ['id' => $availability->id]);
});
