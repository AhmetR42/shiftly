<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvailabilityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'size:7'],
            'days.*.weekday' => ['required', 'integer', 'between:1,7', 'distinct'],
            'days.*.available' => ['required', 'boolean'],
            'days.*.from_time' => ['nullable', 'date_format:H:i'],
            'days.*.until_time' => ['nullable', 'date_format:H:i', 'after:days.*.from_time'],
        ];
    }
}
