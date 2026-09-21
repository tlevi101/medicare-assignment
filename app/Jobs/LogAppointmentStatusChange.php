<?php

namespace App\Jobs;

use App\Enums\AppointmentStatus;
use App\Models\AppointmentStatusActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LogAppointmentStatusChange implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $appointmentId,
        protected AppointmentStatus $previous,
        protected AppointmentStatus $new
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        AppointmentStatusActivity::create([
            'appointment_id' => $this->appointmentId,
            'previous' => $this->previous,
            'new' => $this->new,
        ]);
    }
}
