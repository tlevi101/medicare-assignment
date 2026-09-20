<?php

namespace App\Rules;

use App\Models\Doctor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;

class AvailabilitiesNotOverlap
{
    private const MESSAGE = 'The availability overlaps with an existing availability.';
    public function __construct(
        private Doctor $doctor,
        private ?int $ignore = null
    ) { }
    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['starts_at', 'ends_at'])) {
            return;
        }

        $startsAt = $validator->getData()['starts_at'];
        $endsAt = $validator->getData()['ends_at'];

        $overlaps = $this->doctor->availabilities()
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($this->ignore, fn ($q) => $q->whereKeyNot($this->ignore))
            ->exists();

        if ($overlaps) {
            $validator->errors()->add('starts_at', self::MESSAGE);
            $validator->errors()->add('ends_at', self::MESSAGE);
        }
    }
}
