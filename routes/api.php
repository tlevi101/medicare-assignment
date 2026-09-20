<?php

use Illuminate\Support\Facades\Route;


Route::resource(
    'patients',
    \App\Http\Controllers\PatientController::class,
    ['except' => ['create', 'edit']]
);
Route::resource(
    'doctors',
    \App\Http\Controllers\DoctorController::class,
    ['except' => ['create', 'edit']]
);

Route::resource(
    'doctors.availabilities',
    \App\Http\Controllers\AvailabilityController::class,
    ['except' => ['create', 'edit']]
);
