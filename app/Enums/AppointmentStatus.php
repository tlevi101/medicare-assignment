<?php

namespace App\Enums;

enum AppointmentStatus : string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';

    case Completed = 'completed';

    case Cancelled = 'canceled';

    /**
     * Determinate if a status can move to another status.
     * @param AppointmentStatus $newStatus
     * @return bool
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::Pending => in_array($newStatus, [self::Confirmed, self::Cancelled]),
            self::Confirmed => in_array($newStatus, [self::Completed, self::Cancelled]),
            self::Completed => false,
            self::Cancelled => false,
        };
    }

}
