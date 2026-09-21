<?php

use App\Models\Availability;
use Illuminate\Support\Carbon;


function unsavedAvailability(string $startsAt, string $endsAt, int $slot): Availability
{
    return new Availability([
        'doctor_id' => 1,
        'starts_at' => Carbon::parse($startsAt),
        'ends_at' => Carbon::parse($endsAt),
        'slot' => $slot,
    ]);
}

describe('Availability::$slots', function () {
    it('produces one slot per slot length', function (
        string $startsAt,
        string $endsAt,
        int $slot,
        int $expectedCount,
    ) {
        expect(unsavedAvailability($startsAt, $endsAt, $slot)->slots)
            ->toHaveCount($expectedCount);
    })->with([
        'four 30 minute slots in two hours' => ['2026-06-01 09:00', '2026-06-01 11:00', 30, 4],
        'a single slot filling the window' => ['2026-06-01 09:00', '2026-06-01 10:00', 60, 1],
        'twelve 5 minute slots in an hour' => ['2026-06-01 09:00', '2026-06-01 10:00', 5, 12],
        'a whole working day' => ['2026-06-01 08:00', '2026-06-01 16:00', 15, 32],
    ]);

    it("First slot's starts_at is the availability's starts_at", function () {
        $slots = unsavedAvailability('2026-06-01 09:20', '2026-06-01 10:20', 30)->slots;

        expect($slots->pluck('starts_at')->map->toTimeString()->all())
            ->toBe(['09:20:00', '09:50:00'])
            ->and($slots->last()['ends_at']->toTimeString())->toBe('10:20:00');
    });

    it('carries the doctor of the availability', function () {
        expect(unsavedAvailability('2026-06-01 09:00', '2026-06-01 10:00', 30)->slots
            ->pluck('doctor_id')->unique()->all())->toBe([1]);
    });
});
