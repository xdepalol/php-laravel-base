<?php

namespace App\Http\Requests\Submission;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('grade') && $this->input('grade') === '') {
            $this->merge(['grade' => null]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'grade' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'feedback' => ['nullable', 'string', 'max:65535'],
            'status' => [
                'nullable',
                'integer',
                Rule::in([SubmissionStatus::SUBMITTED->value, SubmissionStatus::GRADED->value]),
            ],
        ];
    }
}
