<?php

namespace App\Rules;

use App\Models\Availability;
use App\Models\Doctor;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;

class AvailabilitiesNotOverlap
{
    private const MESSAGE = 'The availability overlaps with an existing availability.';
    public function __construct(
        private readonly Doctor $doctor,
        private readonly ?Availability $availability = null,
    ) { }
    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['starts_at', 'ends_at'])) {
            return;
        }

        $startsAt = Carbon::parse($validator->getData()['starts_at'] ?? $this->availability?->starts_at);
        $endsAt = Carbon::parse($validator->getData()['ends_at']?? $this->availability?->ends_at);

        $overlaps = $this->doctor->availabilities()
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($this->availability, fn ($q) => $q->whereKeyNot($this->availability->getKey()))
            ->exists();

        if ($overlaps) {
            $validator->errors()->add('starts_at', self::MESSAGE);
            $validator->errors()->add('ends_at', self::MESSAGE);
        }
    }
}
