<?php

namespace App\Rules;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Validation\Validator;

/**
 * Check if the appointment can transition to the given status.
 */
class AppointmentStatusCanTransitionTo
{
    const string MESSAGE = "Appointment status can't be changed to: ";
    const int CANCEL_WINDOW = 24; // In hours
    public function __construct(
        protected AppointmentStatus $targetStatus,
        public Appointment $appointment
    ) {}

    public function __invoke(Validator $validator): void
    {
        if(!$this->appointment->status->canTransitionTo($this->targetStatus)) {
            $validator->errors()->add('status', self::MESSAGE . $this->targetStatus->value);
        }

        if($this->targetStatus === AppointmentStatus::Cancelled &&
            $this->appointment->status === AppointmentStatus::Confirmed &&
            $this->appointment->starts_at->lt(now()->addHours(self::CANCEL_WINDOW))
        ) {
            $validator->errors()->add('status', self::MESSAGE . $this->targetStatus->value);
        }
    }
}
