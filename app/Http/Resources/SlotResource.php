<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class SlotResource extends JsonApiResource
{
    /**
     * Slot is not a model, so we generate an id.
     */
    public function toId(Request $request): string
    {
        return $this->resource['doctor_id'].':'.$this->resource['starts_at']->toIso8601String();
    }

    public function toAttributes($request): array
    {
        return [
            'doctor_id' => $this->resource['doctor_id'],
            'starts_at' => $this->resource['starts_at'],
            'ends_at' => $this->resource['ends_at'],
        ];
    }
}
