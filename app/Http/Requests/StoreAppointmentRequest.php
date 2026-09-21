<?php

namespace App\Http\Requests;

use App\Rules\AppointmentWithinAvailability;
use App\Rules\PatientHasNoConflictingAppointment;
use App\Rules\SlotIsFree;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\Concerns\TruncatesDateTimeToMinutes;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    use TruncatesDateTimeToMinutes;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // Only the keys that were actually submitted are merged back
        $this->merge(array_filter([
            'starts_at' => $this->truncatesDateTimeToMinutes($this->input('starts_at')),
            'ends_at' => $this->truncatesDateTimeToMinutes($this->input('ends_at')),
        ], fn ($value) => $value !== null));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'exists:doctors,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    public function after(): array
    {
        return [
            new AppointmentWithinAvailability(),
            new SlotIsFree(),
            new PatientHasNoConflictingAppointment($this->route('patient')),
        ];
    }
}
