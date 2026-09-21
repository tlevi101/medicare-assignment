<?php

use App\Models\Doctor;

test('doctors index', function () {
    Doctor::factory()->count(3)->create();

    $response = $this->getJson('/api/doctors');
    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('doctors show', function () {
    $doctor = Doctor::factory()->create();

    $response = $this->getJson("/api/doctors/{$doctor->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $doctor->id,
                'type' => 'doctors',
                'attributes' => [
                    'name' => $doctor->name,
                    'email' => $doctor->email,
                    'expertise' => $doctor->expertise,
                ],
            ],
        ]);
});

describe('doctor store: ', function () {
    it('creates a doctor', function () {
        $doctorData = [
            'name' => 'Dr. John Doe',
            'email' => 'test@example.com',
            'expertise' => 'Cardiology',
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'type' => 'doctors',
                    'attributes' => $doctorData,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'expertise',
                    ],
                ],
            ]);
    });

    it('requires name, email and expertise', function () {
        $response = $this->postJson('/api/doctors', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'expertise']);
    });

    it('fails on invalid email', function () {
        $response = $this->postJson('/api/doctors', [
            'name' => 'Dr. John Doe',
            'email' => 'invalid-email',
            'expertise' => 'Cardiology',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when email is not unique', function () {
        $existingDoctor = Doctor::factory()->create();

        $response = $this->postJson('/api/doctors', [
            'name' => 'Dr. John Doe',
            'email' => $existingDoctor->email,
            'expertise' => 'Cardiology',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('fails on too long inputs', function () {
        $response = $this->postJson('/api/doctors', [
            'name' => str_repeat('a', 256),
            'email' => str_repeat('a', 256).'@example.com',
            'expertise' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'expertise']);
    });

});

describe('doctor update: ', function () {
    beforeEach(function () {
        $this->doctor = Doctor::factory()->create();
    });
    it('updates a doctor', function () {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'test@example.com',
            'expertise' => 'Neurology',
        ];
        $response = $this->putJson("/api/doctors/{$this->doctor->id}", $doctorData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'type' => 'doctors',
                    'attributes' => $doctorData,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'expertise',
                    ],
                ],
            ]);
    });

    it('partially updates a doctor', function () {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
        ];

        $response = $this->patchJson("/api/doctors/{$this->doctor->id}", $doctorData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'type' => 'doctors',
                    'attributes' => $doctorData,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'expertise',
                    ],
                ],
            ]);
    });

    it('rejects invalid email', function () {
        $response = $this->putJson("/api/doctors/{$this->doctor->id}", [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when email is not unique', function () {
        $existingDoctor = Doctor::factory()->create();

        $response = $this->putJson("/api/doctors/{$this->doctor->id}", [
            'email' => $existingDoctor->email,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('rejects too long strings', function () {
        $response = $this->putJson("/api/doctors/{$this->doctor->id}", [
            'name' => str_repeat('a', 256),
            'email' => str_repeat('a', 256).'@example.com',
            'expertise' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'expertise']);
    });
});

test('doctors delete', function () {
    $doctor = Doctor::factory()->create();

    $response = $this->deleteJson("/api/doctors/{$doctor->id}");

    $response->assertStatus(204);
});
