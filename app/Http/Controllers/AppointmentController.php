<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\IndexAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;

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
    public function store(Patient $patient, StoreAppointmentRequest $request)
    {
        $appointment = $patient->appointments()->create([
            ...$request->validated(),
            'status' => AppointmentStatus::Pending,
        ]);

        return $appointment->toResource();
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient, Appointment $appointment)
    {
        return $appointment->toResource();
    }


}
