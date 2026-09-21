<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use DateTimeInterface;
use App\Observers\AppointmentObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @method static Builder<static> active() Appointment is considered active if its status is not AppointmentStatus::Cancelled
 * @method static Builder<static> status(AppointmentStatus $status) Filters to a single appointment status
 * @method static Builder<static> overlapping(DateTimeInterface $startsAt, DateTimeInterface $endsAt) Filters to the appointments overlapping the given period
 */
#[Fillable(['patient_id', 'doctor_id', 'starts_at', 'ends_at', 'status', 'cancel_reason'])]
#[ObservedBy(AppointmentObserver::class)]
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

    /**
     * @return HasMany<AppointmentStatusActivity, $this>
     */
    public function statusActivities(): HasMany
    {
        return $this->hasMany(AppointmentStatusActivity::class);
    }

    /**
     * Appointments that overlap the given period.
     */
    #[Scope]
    protected function overlapping(Builder $query, DateTimeInterface $startsAt, DateTimeInterface $endsAt): void
    {
        $query->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);
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
