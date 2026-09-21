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
)->scoped();

Route::resource(
    'patients.appointments',
    \App\Http\Controllers\AppointmentController::class,
    ['only' => ['index', 'store', 'show']]
)->scoped();

Route::get(
    'doctors/{doctor}/free-slots',
    [\App\Http\Controllers\AvailabilityController::class, 'freeSlots']
)->name('doctors.free-slots');

Route::post(
    '/patients/{patient}/appointments/{appointment}/cancel',
    [\App\Http\Controllers\AppointmentController::class, 'cancel']
)->scopeBindings()->name('appointments.cancel');

Route::post(
    'patients/{patient}/appointments/{appointment}/confirm',
    [\App\Http\Controllers\AppointmentController::class, 'confirm']
)->scopeBindings()->name('appointments.confirm');

Route::post(
    'patients/{patient}/appointments/{appointment}/complete',
    [\App\Http\Controllers\AppointmentController::class, 'complete']
)->scopeBindings()->name('appointments.complete');

