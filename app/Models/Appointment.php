<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @method static Builder<static> active() Appointment is considered active if its status is not AppointmentStatus::Cancelled
 * @method static Builder<static> status(AppointmentStatus $status) Filters to a single appointment status
 */
#[Fillable(['patient_id', 'doctor_id', 'starts_at', 'ends_at', 'status', 'cancel_reason'])]
class Appointment extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    #[Scope]
    protected function status(Builder $query, AppointmentStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Appointment considered to be active if status != AppointmentStatus::Cancelled
     * @param Builder $query
     * @return void
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', '!=', AppointmentStatus::Cancelled);
    }
}
