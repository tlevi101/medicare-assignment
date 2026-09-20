<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAvailabilityRequest;
use App\Http\Requests\UpdateAvailabilityRequest;
use App\Models\Availability;
use App\Models\Doctor;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Doctor $doctor, Request $request)
    {
        return $doctor->availabilities()
            ->paginate(perPage: $request->perPage ?? 25)
            ->toResourceCollection();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAvailabilityRequest $request, Doctor $doctor)
    {
        $availability = $doctor->availabilities()->create($request->validated());

        return $availability->toResource();
    }

    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor, Availability $availability)
    {
        return $availability->toResource();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAvailabilityRequest $request, Doctor $doctor, Availability $availability)
    {
        $availability->update($request->validated());

        return $availability->toResource();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doctor $doctor, Availability $availability)
    {
        $availability->delete();

        return response()->noContent();
    }
}
