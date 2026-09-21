<?php

namespace App\Http\Requests\AppointmentStatus;

use App\Enums\AppointmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;

class AppointmentConfirmRequest extends AppointmentTransitionRequest
{
    public function targetStatus(): AppointmentStatus
    {
        return AppointmentStatus::Confirmed;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
