<?php

namespace App\Http\Controllers;

use App\Http\Requests\FreeSlotsRequest;
use App\Http\Requests\StoreAvailabilityRequest;
use App\Http\Requests\UpdateAvailabilityRequest;
use App\Models\Availability;
use App\Http\Resources\SlotResource;
use App\Models\Doctor;
use App\Services\AvailabilitiesSlotsService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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
     * Returns the doctor's free slots
     */
    public function freeSlots(
        FreeSlotsRequest $request,
        Doctor $doctor,
        AvailabilitiesSlotsService $slots,
    ) {
        $from = $request->from();
        $to = $request->to();

        $availabilities = $doctor->availabilities()
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->get();

        // Some slots could be outside the filtered period, we filter them out here
        $freeSlots = $slots->getFreeSlots($availabilities)
            ->filter(fn (array $slot) => $slot['starts_at'] >= $from && $slot['ends_at'] <= $to)
            ->values();

        // Slots are not a Model, so we need to build the Paginator manually
        $page = new LengthAwarePaginator(
            $freeSlots->forPage($request->page(), $request->perPage())->values(),
            $freeSlots->count(),
            $request->perPage(),
            $request->page(),
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return SlotResource::collection($page);
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
