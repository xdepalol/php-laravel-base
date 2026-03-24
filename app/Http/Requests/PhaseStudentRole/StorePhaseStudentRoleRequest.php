<?php

namespace App\Http\Requests\PhaseStudentRole;

use Illuminate\Foundation\Http\FormRequest;

class StorePhaseStudentRoleRequest extends FormRequest
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
            'student_id' => ['required', 'exists:students,user_id'],
            'team_id' => ['required', 'exists:teams,id'],
            'activity_role_id' => ['required', 'exists:activity_roles,id'],
        ];
    }
}
