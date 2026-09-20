<?php

use Illuminate\Support\Facades\Route;


Route::resource('patients', \App\Http\Controllers\PatientController::class);
Route::resource('doctors', \App\Http\Controllers\DoctorController::class);
