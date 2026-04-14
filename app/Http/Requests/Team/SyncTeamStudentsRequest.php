<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class SyncTeamStudentsRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // `present` permite array vacío; `required` falla con [] y impedía dejar el equipo sin miembros.
            'students' => ['present', 'array'],
            'students.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,user_id'],
            'students.*.activity_role_id' => ['nullable', 'integer', 'exists:activity_roles,id'],
        ];
    }
}
