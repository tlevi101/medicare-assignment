<?php

namespace App\Models;

use App\Http\Resources\DoctorResource;
use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'expertise'])]
#[UseResource(DoctorResource::class)]
#[UseFactory(DoctorFactory::class)]
class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    /**
     * @return HasMany<Availability>
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }
}
