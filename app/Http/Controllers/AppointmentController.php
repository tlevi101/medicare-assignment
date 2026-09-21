<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Patient $patient)
    {
        return $patient->appointments()
            ->paginate(perPage: $request->perPage ?? 25)
            ->toResourceCollection();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Patient $patient, StoreAppointmentRequest $request)
    {
        $appointment = $patient->appointments()->create($request->validated());

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
