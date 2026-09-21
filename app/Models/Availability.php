<?php

namespace App\Models;

use App\Http\Resources\AvailabilityResource;
use Carbon\Carbon;
use Database\Factories\AvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection as SupportCollection;

#[Fillable(['doctor_id', 'starts_at', 'ends_at', 'slot'])]
#[UseResource(AvailabilityResource::class)]
#[UseFactory(AvailabilityFactory::class)]
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function slots(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculateSlots(),
        );
    }

    /**
     * @return SupportCollection<int, array{doctor_id: int, starts_at: Carbon, ends_at: Carbon}>
     */
    protected function calculateSlots(): SupportCollection
    {
        $diffInMinutes = (int) $this->starts_at->diffInMinutes($this->ends_at);
        $slotCount = (int) ($diffInMinutes / $this->slot);
        $result = collect([]);
        for ($i = 0; $i < $slotCount; $i++) {
            $result->add(
                [
                    'doctor_id' => $this->doctor_id,
                    'starts_at' => (clone $this->starts_at)->addMinutes($this->slot * $i),
                    'ends_at' => (clone $this->starts_at)->addMinutes($this->slot * ($i + 1)),
                ]
            );
        }

        return $result;
    }
}
