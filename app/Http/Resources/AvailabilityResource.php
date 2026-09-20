<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @mixin \App\Models\Availability
 */
class AvailabilityResource extends JsonApiResource
{
    public function toAttributes($request): array
    {
        return [
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'slot' => $this->slot,
        ];
    }

    public function toRelationships(Request $request)
    {
        return [
            'doctor' => DoctorResource::class,
        ];
    }
}
