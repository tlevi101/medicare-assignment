<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class FreeSlotsRequest extends FormRequest
{
    public const MAX_PERIOD_IN_DAYS = 14;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after:from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['from', 'to'])) {
                    return;
                }

                if ($this->to()->gt($this->from()->copy()->addDays(self::MAX_PERIOD_IN_DAYS))) {
                    $validator->errors()->add(
                        'to',
                        'The queried period must not be longer than '.self::MAX_PERIOD_IN_DAYS.' days.'
                    );
                }
            },
        ];
    }

    public function from(): Carbon
    {
        return Carbon::parse($this->input('from', 'now'))->max(now());
    }

    public function to(): Carbon
    {
        return $this->filled('to')
            ? Carbon::parse($this->input('to'))
            : $this->from()->copy()->addDays(self::MAX_PERIOD_IN_DAYS);
    }

    public function perPage(): int
    {
        return (int) $this->input('perPage', 25);
    }

    public function page(): int
    {
        return (int) $this->input('page', 1);
    }
}
