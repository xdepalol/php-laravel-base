<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class IndexMyStudentSubjectGroupsRequest extends FormRequest
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
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ];
    }
}
