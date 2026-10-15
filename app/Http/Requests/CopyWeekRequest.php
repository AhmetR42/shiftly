<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopyWeekRequest extends FormRequest
{
    public function rules(): array
    {
        return ['week' => ['required', 'date_format:Y-m-d']];
    }
}
