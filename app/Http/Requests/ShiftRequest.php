<?php

namespace App\Http\Requests;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both creating and updating. On update every field is optional,
 * so dragging a shift to another day only has to send the new date.
 */
class ShiftRequest extends FormRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';
        $teamId = $this->user()->team_id;

        return [
            'user_id' => [$required, 'integer', Rule::exists('users', 'id')->where('team_id', $teamId)],
            'date' => [$required, 'date_format:Y-m-d'],
            'start_time' => [$required, 'date_format:H:i'],
            'end_time' => [$required, 'date_format:H:i'],
            'department' => [$required, Rule::in(Shift::DEPARTMENTS)],
            'note' => ['nullable', 'string', 'max:140'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $shift = $this->route('shift');
                $start = $this->input('start_time', $shift?->start_time);
                $end = $this->input('end_time', $shift?->end_time);

                if ($start && $end && $validator->errors()->isEmpty() && $end <= $start) {
                    $validator->errors()->add('end_time', 'The end time must be after the start time.');
                }
            },
        ];
    }
}
