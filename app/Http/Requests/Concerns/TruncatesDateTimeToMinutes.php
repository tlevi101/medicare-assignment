<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Carbon;

/**
 * Truncating to minutes avoids the issue when comparing dates with seconds and milliseconds,
 * which can cause unexpected behavior in validation and comparisons.
 */
trait TruncatesDateTimeToMinutes
{
    /**
     * Try to parse string to carbon, get the start of minute and then return an iso8601 string
     */
    protected function truncatesDateTimeToMinutes(?string $dateTime): ?string
    {
        if (blank($dateTime)) {
            return $dateTime;
        }

        try {
            return Carbon::parse($dateTime)->startOfMinute()->toIso8601String();
        } catch (\Exception $e) {
            return $dateTime;
        }
    }
}
