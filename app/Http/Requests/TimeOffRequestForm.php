<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimeOffRequestForm extends FormRequest
{
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:200'],
        ];
    }
}
