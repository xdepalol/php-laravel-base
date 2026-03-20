<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnrollmentRequest extends FormRequest
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
            // El student_id ha d'existir a la taula students (columna user_id)
            'student_id' => [
                'required',
                'exists:students,user_id',
                // Validem que la combinació student/subject_group no estigui repetida
                Rule::unique('enrollments')->where(function ($query) {
                    return $query->where('subject_group_id', $this->subject_group_id);
                })->ignore($this->enrollment) // Ignora updates
            ],
            'subject_group_id' => 'required|exists:subject_groups,id',
            'status' => [
                'nullable', 
                Rule::enum(EnrollmentStatus::class)
            ],
        ];
    }
}
