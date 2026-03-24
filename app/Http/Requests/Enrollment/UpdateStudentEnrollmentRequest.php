<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentEnrollmentRequest extends FormRequest
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
        $student = $this->route('student');
        $studentId = $student instanceof Student ? $student->getKey() : $student;

        $enrollment = $this->route('enrollment');

        return [
            'subject_group_id' => [
                'required',
                'exists:subject_groups,id',
                Rule::unique('enrollments', 'subject_group_id')
                    ->where('student_id', $studentId)
                    ->ignore($enrollment instanceof Enrollment ? $enrollment->id : $enrollment),
            ],
            'status' => ['nullable', Rule::enum(EnrollmentStatus::class)],
        ];
    }
}
