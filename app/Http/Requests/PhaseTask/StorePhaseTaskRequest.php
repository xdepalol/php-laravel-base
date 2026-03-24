<?php

namespace App\Http\Requests\PhaseTask;

use Illuminate\Foundation\Http\FormRequest;

class StorePhaseTaskRequest extends FormRequest
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
            'phase_id' => ['required', 'exists:phases,id'],
            'task_id' => ['required', 'exists:tasks,id'],
            'student_id' => ['nullable', 'exists:students,user_id'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
