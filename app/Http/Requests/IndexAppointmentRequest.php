<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAppointmentRequest extends FormRequest
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
     * `filter` follows the JSON:API convention of reserving the parameter name
     * while leaving its contents to the application.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.status' => ['sometimes', Rule::enum(AppointmentStatus::class)],
        ];
    }

    public function status(): ?AppointmentStatus
    {
        $status = $this->validated('filter.status');

        return $status === null ? null : AppointmentStatus::from($status);
    }
}
