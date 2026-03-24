<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\SubjectGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnrollmentSubjectGroupRequest extends FormRequest
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
        $subjectGroup = $this->route('subject_group');
        $subjectGroupId = $subjectGroup instanceof SubjectGroup ? $subjectGroup->id : $subjectGroup;

        $enrollment = $this->route('enrollment');

        return [
            'student_id' => [
                'required',
                'exists:students,user_id',
                Rule::unique('enrollments', 'student_id')
                    ->where('subject_group_id', $subjectGroupId)
                    ->ignore($enrollment instanceof Enrollment ? $enrollment->id : $enrollment),
            ],
            'status' => ['nullable', Rule::enum(EnrollmentStatus::class)],
        ];
    }
}
