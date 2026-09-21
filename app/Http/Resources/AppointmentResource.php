<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @mixin \App\Models\Appointment
 */
class AppointmentResource extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'cancel_reason' => $this->cancel_reason,
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'patient' => PatientResource::class,
            'doctor' => DoctorResource::class
        ];
    }
}
