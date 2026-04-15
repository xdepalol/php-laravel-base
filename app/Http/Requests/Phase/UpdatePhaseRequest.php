<?php

namespace App\Http\Requests\Phase;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'is_sprint' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'retro_well' => ['nullable', 'string'],
            'retro_bad' => ['nullable', 'string'],
            'retro_improvement' => ['nullable', 'string'],
            'teacher_feedback' => ['nullable', 'string'],
            'teams_may_assign_phase_roles' => ['boolean'],
        ];
    }
}
