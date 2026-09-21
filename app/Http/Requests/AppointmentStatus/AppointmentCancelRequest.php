<?php

namespace App\Http\Requests\AppointmentStatus;

use App\Enums\AppointmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;

class AppointmentCancelRequest extends AppointmentTransitionRequest
{
    public function targetStatus(): AppointmentStatus
    {
        return AppointmentStatus::Cancelled;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
