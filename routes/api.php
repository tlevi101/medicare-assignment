<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::resource(
    'patients',
    PatientController::class,
    ['except' => ['create', 'edit']]
);
Route::resource(
    'doctors',
    DoctorController::class,
    ['except' => ['create', 'edit']]
);

Route::resource(
    'doctors.availabilities',
    AvailabilityController::class,
    ['except' => ['create', 'edit']]
)->scoped();

Route::resource(
    'patients.appointments',
    AppointmentController::class,
    ['only' => ['index', 'store', 'show']]
)->scoped();

Route::get(
    'doctors/{doctor}/free-slots',
    [AvailabilityController::class, 'freeSlots']
)->name('doctors.free-slots');

Route::post(
    '/patients/{patient}/appointments/{appointment}/cancel',
    [AppointmentController::class, 'cancel']
)->scopeBindings()->name('appointments.cancel');

Route::post(
    'patients/{patient}/appointments/{appointment}/confirm',
    [AppointmentController::class, 'confirm']
)->scopeBindings()->name('appointments.confirm');

Route::post(
    'patients/{patient}/appointments/{appointment}/complete',
    [AppointmentController::class, 'complete']
)->scopeBindings()->name('appointments.complete');
