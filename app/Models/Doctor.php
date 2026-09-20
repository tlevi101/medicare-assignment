<?php

namespace App\Models;

use App\Http\Resources\DoctorResource;
use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'expertise'])]
#[UseResource(DoctorResource::class)]
#[UseFactory(DoctorFactory::class)]
class Doctor extends Model
{
    use HasFactory;
}
