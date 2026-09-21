<?php

namespace App\Rules;

use App\Models\Availability;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Checks if the requested period falls within the availability of the doctor and if it matches the slot.
 */
class AppointmentWithinAvailability
{
    private const MESSAGE_OUTSIDE_AVAILABILITY = 'The requested period does not fall within any availability of the doctor.';

    private const MESSAGE_NOT_A_SLOT = 'The requested period is not a bookable slot of the availability.';

    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['starts_at', 'ends_at', 'doctor_id'])) {
            return;
        }

        $startsAt = Carbon::parse($validator->getData()['starts_at']);
        $endsAt = Carbon::parse($validator->getData()['ends_at']);
        $doctorId = $validator->getData()['doctor_id'];

        // Check if the doctor is available at that time period,
        // note: this doesn't check if the slot is free
        $availability = Availability::query()
            ->where('doctor_id', $doctorId)
            ->where('starts_at', '<=', $startsAt)
            ->where('ends_at', '>=', $endsAt)
            ->first();

        // contain the requested period.
        if (!$availability) {
            $this->fail($validator, self::MESSAGE_OUTSIDE_AVAILABILITY);

            return;
        }

        $periodInMinutes = (int) $startsAt->diffInMinutes($endsAt);
        $offsetInMinutes = (int) $availability->starts_at->diffInMinutes($startsAt);
        // This condition checks if the period matches the doctor's slot
        // and if the start time is aligned with the slot intervals.
        if ($periodInMinutes !== $availability->slot || $offsetInMinutes % $availability->slot !== 0) {
            $this->fail($validator, self::MESSAGE_NOT_A_SLOT);
        }
    }

    private function fail(Validator $validator, string $message): void
    {
        $validator->errors()->add('starts_at', $message);
        $validator->errors()->add('ends_at', $message);
    }
}
