<?php

namespace App\Rules;

use App\Models\Patient;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Checks if the patient already has an active appointment overlapping the
 * requested period, with any doctor.
 */
class PatientHasNoConflictingAppointment
{
    private const MESSAGE = 'The patient already has an appointment in this period.';

    public function __construct(
        private readonly Patient $patient,
    ) { }

    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['starts_at', 'ends_at'])) {
            return;
        }

        $startsAt = Carbon::parse($validator->getData()['starts_at']);
        $endsAt = Carbon::parse($validator->getData()['ends_at']);

        $conflicts = $this->patient->appointments()
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->active()
            ->exists();

        if ($conflicts) {
            $validator->errors()->add('starts_at', self::MESSAGE);
            $validator->errors()->add('ends_at', self::MESSAGE);
        }
    }
}
