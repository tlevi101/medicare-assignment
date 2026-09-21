<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('appointment_id', 'previous', 'new')]
class AppointmentStatusActivity extends Model
{
    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function casts(): array
    {
        return [
            'previous' => AppointmentStatus::class,
            'new' => AppointmentStatus::class,
        ];
    }
}
