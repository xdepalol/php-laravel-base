<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\SubjectGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentSubjectGroupRequest extends FormRequest
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

        return [
            'student_id' => [
                'required',
                'exists:students,user_id',
                Rule::unique('enrollments', 'student_id')->where('subject_group_id', $subjectGroupId),
            ],
            'status' => ['nullable', Rule::enum(EnrollmentStatus::class)],
        ];
    }
}
