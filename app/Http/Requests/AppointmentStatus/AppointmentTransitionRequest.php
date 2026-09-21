<?php

namespace App\Http\Requests\AppointmentStatus;

use App\Enums\AppointmentStatus;
use App\Rules\AppointmentStatusCanTransitionTo;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base request for appointment status change requests.
 */
abstract class AppointmentTransitionRequest extends FormRequest
{
    /**
     * The status this request moves the appointment to.
     */
    abstract public function targetStatus(): AppointmentStatus;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<int, object>
     */
    public function after(): array
    {
        return [
            new AppointmentStatusCanTransitionTo(
                $this->targetStatus(),
                $this->route('appointment')
            ),
        ];
    }
}
