<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Availability;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Can be used to retreive the slots ands free for availabilities
 */
class AvailabilitiesSlotsService
{
    /**
     * @param  Collection<int, Availability>  $availabilities
     * @return SupportCollection<int, array{doctor_id: int, starts_at: Carbon, ends_at: Carbon}>
     */
    public function getFreeSlots(Collection $availabilities): SupportCollection
    {
        if ($availabilities->isEmpty()) {
            return collect();
        }

        $reservedSlots = $this->getReservedSlots(
            doctorIds: $availabilities->pluck('doctor_id')->unique()->toArray(),
            startsAt: $availabilities->min('starts_at'),
            endsAt: $availabilities->max('ends_at'),
        )->groupBy('doctor_id');

        return $this->getSlots($availabilities)
            ->reject(function (array $slot) use ($reservedSlots) {
                return $reservedSlots->get($slot['doctor_id'], collect())
                    ->contains(function (Appointment $appointment) use ($slot) {
                        return $appointment->starts_at < $slot['ends_at']
                            && $appointment->ends_at > $slot['starts_at'];
                    });
            })
            ->values();
    }

    /**
     * Get the collapsed slots for each availabilites
     * @param  Collection<int, Availability>  $availabilities
     * @return SupportCollection<int, array{doctor_id: int, starts_at: Carbon, ends_at: Carbon}>
     */
    public function getSlots(Collection $availabilities): SupportCollection
    {
        return $availabilities
            ->map(fn (Availability $availability) => $availability->slots)
            ->collapse();
    }

    /**
     * Return all appointments for availabilities
     * @param  array<int, int>  $doctorIds
     * @param \DateTime $startsAt Min startsAt time of the availabilities
     * @param \DateTime $endsAt Max $endsAt time of the availabilities
     * @return Collection<int, Appointment>
     */
    protected function getReservedSlots(array $doctorIds, \DateTime $startsAt, \DateTime $endsAt): Collection
    {
        return Appointment::query()
            ->whereIn('doctor_id', $doctorIds)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->active()
            ->get();
    }
}
