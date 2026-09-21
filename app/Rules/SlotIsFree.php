<?php

namespace App\Rules;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;


/**
 * Checks if any other patient has an active appointment to the same doctor in the same time slot.
 */
class SlotIsFree
{
    const MESSAGE = 'Slot is not free';
    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['starts_at', 'ends_at', 'doctor_id'])) {
            return;
        }

        $startsAt = Carbon::parse($validator->getData()['starts_at']);
        $endsAt = Carbon::parse($validator->getData()['ends_at']);
        $doctorId = $validator->getData()['doctor_id'];

        $appointmentExists = Appointment::where('doctor_id', $doctorId)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->active()
            ->exists();

        if($appointmentExists) {
            $validator->errors()->add('starts_at', self::MESSAGE);
            $validator->errors()->add('ends_at', self::MESSAGE);
        }

    }
}
