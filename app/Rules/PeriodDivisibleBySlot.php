<?php

namespace App\Rules;

use App\Models\Availability;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Checks if the period between starts_at and ends_at is divisible by the slot.
 * @note This rule was not part of the task, if it was a real task I would have asked the client/PM if we need this or round the ends_at to the nearest slot.
 */
class PeriodDivisibleBySlot
{
    public function __construct(
        private readonly ?Availability $availability = null,
    )
    {

    }

    private const MESSAGE = "The period between starts_at and ends_at must be divisible by the slot.";
    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['starts_at', 'ends_at', 'slot'])) {
            return;
        }

        $startsAt = Carbon::parse($validator->getData()['starts_at'] ?? $this->availability?->starts_at);
        $endsAt = Carbon::parse($validator->getData()['ends_at'] ?? $this->availability?->ends_at);
        $slot = $validator->getData()['slot'] ?? $this->availability?->slot;

        $periodInMinutes = $startsAt->diffInMinutes($endsAt);

        if ($periodInMinutes % $slot !== 0) {
            $validator->errors()->add('starts_at', self::MESSAGE);
            $validator->errors()->add('ends_at', self::MESSAGE);
        }
    }
}
