<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Doctor::paginate(perPage: $request->perPage ?? 25)
            ->toResourceCollection();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDoctorRequest $request)
    {
        $doctor = Doctor::create($request->validated());

        return $doctor->toResource();
    }

    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor)
    {
        return $doctor->toResource()
            ->ignoreFieldsAndIncludesInQueryString();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDoctorRequest $request, Doctor $doctor)
    {
        $doctor->update($request->validated());

        return $doctor->toResource()
            ->ignoreFieldsAndIncludesInQueryString();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doctor $doctor)
    {
        $doctor->delete();

        return response()->noContent();
    }
}
