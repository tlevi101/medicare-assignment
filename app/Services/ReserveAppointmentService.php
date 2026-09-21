<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Patient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveAppointmentService
{
    public const string SLOT_RESERVED = 'Slot is not free';

    public const string PATIENT_BUSY = 'The patient already has an appointment in this period.';

    /**
     * Reserve an appointment for the patient. It first locks the table for updates, then checks if it can reserve the slot.
     * If not throws validation exception.
     *
     * @param  array{doctor_id: int, starts_at: string, ends_at: string}  $attributes
     *
     * @throws ValidationException|\Throwable
     */
    public function reserve(Patient $patient, array $attributes): Appointment
    {
        $startsAt = Carbon::parse($attributes['starts_at']);
        $endsAt = Carbon::parse($attributes['ends_at']);

        return DB::transaction(function () use ($patient, $attributes, $startsAt, $endsAt) {
            Availability::query()
                ->where('doctor_id', $attributes['doctor_id'])
                ->where('starts_at', '<=', $startsAt)
                ->where('ends_at', '>=', $endsAt)
                ->lockForUpdate()
                ->first();

            $this->checkIfSlotIsFree($attributes['doctor_id'], $startsAt, $endsAt);
            $this->checkIfPatientIsFree($patient, $startsAt, $endsAt);

            return $patient->appointments()->create([
                ...$attributes,
                'status' => AppointmentStatus::Pending,
            ]);
        }, attempts: 3);
    }

    /**
     * Check the slot if its taken already or not
     *
     * @throws ValidationException
     */
    private function checkIfSlotIsFree(int $doctorId, Carbon $startsAt, Carbon $endsAt): void
    {
        $reserved = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->overlapping($startsAt, $endsAt)
            ->active()
            ->exists();

        if ($reserved) {
            $this->reject(self::SLOT_RESERVED);
        }
    }

    /**
     * Check if the patient already has an active appointment. Throw validation error if they have
     *
     * @throws ValidationException
     */
    private function checkIfPatientIsFree(Patient $patient, Carbon $startsAt, Carbon $endsAt): void
    {
        $conflicts = $patient->appointments()
            ->overlapping($startsAt, $endsAt)
            ->active()
            ->exists();

        if ($conflicts) {
            $this->reject(self::PATIENT_BUSY);
        }
    }

    /**
     * When a condition fails it throws an exception
     *
     * @throws ValidationException
     */
    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'starts_at' => $message,
            'ends_at' => $message,
        ]);
    }
}
