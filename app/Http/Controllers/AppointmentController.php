<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentStatus\AppointmentCancelRequest;
use App\Http\Requests\AppointmentStatus\AppointmentCompleteRequest;
use App\Http\Requests\AppointmentStatus\AppointmentConfirmRequest;
use App\Http\Requests\IndexAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Services\ReserveAppointmentService;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexAppointmentRequest $request, Patient $patient)
    {
        return $patient->appointments()
            ->when(
                $request->status(),
                fn ($query, AppointmentStatus $status) => $query->status($status)
            )
            ->paginate(perPage: $request->perPage ?? 25)
            ->toResourceCollection();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        Patient $patient,
        StoreAppointmentRequest $request,
        ReserveAppointmentService $booker,
    ) {
        return $booker->reserve($patient, $request->validated())->toResource();
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient, Appointment $appointment)
    {
        return $appointment->toResource();
    }

    public function cancel(AppointmentCancelRequest $request, Patient $patient, Appointment $appointment)
    {
        $appointment->update([
            ...$request->validated(),
            'status' => $request->targetStatus(),
        ]);

        return $appointment->toResource();
    }

    public function complete(AppointmentCompleteRequest $request, Patient $patient, Appointment $appointment)
    {
        $appointment->update([
            ...$request->validated(),
            'status' => $request->targetStatus(),
        ]);

        return $appointment->toResource();
    }

    public function confirm(AppointmentConfirmRequest $request, Patient $patient, Appointment $appointment)
    {
        $appointment->update([
            ...$request->validated(),
            'status' => $request->targetStatus(),
        ]);

        return $appointment->toResource();
    }
}
