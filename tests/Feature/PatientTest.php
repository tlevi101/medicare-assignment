<?php

test('patients index', function () {
    \App\Models\Patient::factory()->count(3)->create();

    $response = $this->getJson('/api/patients');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('patients show', function () {
    $patient = \App\Models\Patient::factory()->create();

    $response = $this->getJson("/api/patients/{$patient->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $patient->id,
                'type' => 'patients',
                'attributes' => [
                    'name' => $patient->name,
                    'email' => $patient->email,
                    'phone' => $patient->phone,
                ]
            ],
        ]);
});

test('patients store', function () {
    $patientData = [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'phone' => '1234567890',
    ];

    $response = $this->postJson('/api/patients', $patientData);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'type' => 'patients',
                'attributes' => $patientData,
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'email',
                    'phone',
                ],
            ],
        ]);
});

describe('patient store:', function () {
    it('requires name, email, phone', function () {
        $response = $this->postJson('/api/patients', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone']);
    });

    it('fails when email is not unique', function () {
        $existingPatient = \App\Models\Patient::factory()->create();

        $response = $this->postJson('/api/patients', [
            'name' => 'John Doe',
            'email' => $existingPatient->email,
            'phone' => '1234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when email is not valid', function () {
        $response = $this->postJson('/api/patients', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'phone' => '1234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('rejects too long strings', function() {
        $response = $this->postJson('/api/patients', [
            'name' => str_repeat('a', 256),
            'email' => str_repeat('a', 256) . '@example.com',
            'phone' => str_repeat('1', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone']);
    });
});

describe('patient update:', function () {
    beforeEach(function () {
        $this->patient = \App\Models\Patient::factory()->create();
    });
    it('updates a patient', function () {
        $updatedData = [
            'name' => 'Jane Doe',
            'email' => 'test@example.com',
            'phone' => '1234567890'
        ];

        $response = $this->putJson("/api/patients/{$this->patient->id}", $updatedData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->patient->id,
                    'type' => 'patients',
                    'attributes' => $updatedData,
                ],
            ]);
    });
    it('partially updates a patient', function() {
        $updatedData = [
            'name' => 'Jane Doe',
        ];

        $response = $this->patchJson("/api/patients/{$this->patient->id}", $updatedData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->patient->id,
                    'type' => 'patients',
                    'attributes' => array_merge($this->patient->only(['email', 'phone']), $updatedData),
                ],
            ]);
    });
    it('rejects invalid email', function () {
        $patient = \App\Models\Patient::factory()->create();

        $response = $this->putJson("/api/patients/{$patient->id}", [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('requires unique email', function () {
        $existingPatient = \App\Models\Patient::factory()->create();
        $patientToUpdate = \App\Models\Patient::factory()->create();

        $response = $this->putJson("/api/patients/{$patientToUpdate->id}", [
            'email' => $existingPatient->email,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});

test('patient delete', function () {
    $patient = \App\Models\Patient::factory()->create();

    $response = $this->deleteJson("/api/patients/{$patient->id}");

    $response->assertStatus(204);
});


