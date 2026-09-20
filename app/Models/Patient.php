<?php

namespace App\Models;

use App\Http\Resources\PatientResource;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['name', 'email', 'phone'])]
#[UseResource(PatientResource::class)]
#[UseFactory(PatientFactory::class)]
class Patient extends Model
{
    use HasFactory;
}
