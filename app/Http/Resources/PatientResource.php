<?php

namespace App\Http\Resources;

use App\Models\Patient;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @mixin Patient
 */
class PatientResource extends JsonApiResource
{
    public function toAttributes($request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

    /**
     * The resource's relationships.
     */
    public $relationships = [
        // ...
    ];
}
