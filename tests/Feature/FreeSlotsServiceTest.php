<?php

use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Services\AvailabilitiesSlotsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Creates a new appointment for a doctor
 *
 * @param  int  $offsetInMinutes  Distance of the booked slot from $windowStartsAt.
 * @param  bool  $cancelled  A cancelled appointment must not hold its slot.
 */
function createAppointmentAt(
    Doctor $doctor,
    Carbon $windowStartsAt,
    int $offsetInMinutes,
    bool $cancelled = false,
): Appointment {
    $factory = $cancelled
        ? Appointment::factory()->cancelled()
        : Appointment::factory();

    return $factory->create([
        'doctor_id' => $doctor->id,
        'starts_at' => (clone $windowStartsAt)->addMinutes($offsetInMinutes),
        'ends_at' => (clone $windowStartsAt)->addMinutes($offsetInMinutes + 30),
    ]);
}

/**
 * Runs the service over every availability of the given doctors.
 *
 * @param  Doctor  ...$doctors
 * @return Collection<int, array{doctor_id: int, starts_at: Carbon, ends_at: Carbon}>
 */
function freeSlotsOf(Doctor ...$doctors): Collection
{
    $availabilities = Availability::query()
        ->whereIn('doctor_id', array_column($doctors, 'id'))
        ->get();

    return (new AvailabilitiesSlotsService())->getFreeSlots($availabilities);
}

beforeEach(function () {
    $this->doctor = Doctor::factory()->create();

    $this->startsAt = now()->addDay()->startOfHour();
    Availability::factory()->create([
        'doctor_id' => $this->doctor->id,
        'starts_at' => $this->startsAt,
        'ends_at' => (clone $this->startsAt)->addHours(2),
        'slot' => 30,
    ]);
});

it('lists every slot when nothing is booked', function () {
    expect(freeSlotsOf($this->doctor))->toHaveCount(4);
});

it('excludes a booked slot at the start of the window', function () {
    createAppointmentAt($this->doctor, $this->startsAt, 0);

    $free = freeSlotsOf($this->doctor);

    expect($free)->toHaveCount(3)
        ->and($free->first()['starts_at']->eq((clone $this->startsAt)->addMinutes(30)))->toBeTrue();
});

it('excludes a booked slot at the end of the window', function () {
    createAppointmentAt($this->doctor, $this->startsAt, 90);

    $free = freeSlotsOf($this->doctor);

    expect($free)->toHaveCount(3)
        ->and($free->last()['ends_at']->eq((clone $this->startsAt)->addMinutes(90)))->toBeTrue();
});

it('excludes a booked slot in the middle of the window', function () {
    createAppointmentAt($this->doctor, $this->startsAt, 30);

    $free = freeSlotsOf($this->doctor);

    expect($free)->toHaveCount(3)
        ->and($free->pluck('starts_at')->contains(fn (Carbon $startsAt) => $startsAt->eq(
            (clone $this->startsAt)->addMinutes(30)
        )))->toBeFalse();
});

it('keeps a slot whose appointment was cancelled', function () {
    createAppointmentAt($this->doctor, $this->startsAt, 0, cancelled: true);

    expect(freeSlotsOf($this->doctor))->toHaveCount(4);
});

it('keeps a slot booked at another doctor', function () {
    createAppointmentAt(Doctor::factory()->create(), $this->startsAt, 0);

    expect(freeSlotsOf($this->doctor))->toHaveCount(4);
});

it('returns an empty collection when there is no availability', function () {
    expect(freeSlotsOf(Doctor::factory()->create()))->toBeEmpty();
});

it('keeps the slots of several doctors apart', function () {
    $otherDoctor = Doctor::factory()->create();
    Availability::factory()->create([
        'doctor_id' => $otherDoctor->id,
        'starts_at' => $this->startsAt,
        'ends_at' => (clone $this->startsAt)->addHours(2),
        'slot' => 30,
    ]);
    createAppointmentAt($this->doctor, $this->startsAt, 0);

    $free = freeSlotsOf($this->doctor, $otherDoctor);

    expect($free)->toHaveCount(7)
        ->and($free->where('doctor_id', $this->doctor->id))->toHaveCount(3)
        ->and($free->where('doctor_id', $otherDoctor->id))->toHaveCount(4);
});
