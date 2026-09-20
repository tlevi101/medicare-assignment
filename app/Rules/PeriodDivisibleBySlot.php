<?php

namespace App\Rules;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;

class PeriodDivisibleBySlot
{

    private const MESSAGE = "The period between starts_at and ends_at must be divisible by the slot.";
    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['starts_at', 'ends_at', 'slot'])) {
            return;
        }

        $startsAt = Carbon::parse($validator->getData()['starts_at']);
        $endsAt = Carbon::parse($validator->getData()['ends_at']);
        $slot = $validator->getData()['slot'];

        $periodInMinutes = $startsAt->diffInMinutes($endsAt);

        if ($periodInMinutes % $slot !== 0) {
            $validator->errors()->add('starts_at', self::MESSAGE);
            $validator->errors()->add('ends_at', self::MESSAGE);
        }
    }
}
