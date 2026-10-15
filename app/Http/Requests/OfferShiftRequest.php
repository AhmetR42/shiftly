<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfferShiftRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'note' => ['nullable', 'string', 'max:140'],
        ];
    }
}
