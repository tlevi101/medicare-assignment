<?php

namespace App\Http\Requests;

use App\Rules\AvailabilitiesNotOverlap;
use App\Rules\PeriodDivisibleBySlot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date', 'before:ends_at', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'slot' => ['required', 'integer', 'min:30'],
        ];
    }

    public function after(): array
    {
        return [
            new AvailabilitiesNotOverlap(
                doctor: $this->route('doctor')
            ),
            new PeriodDivisibleBySlot(),
        ];
    }
}
