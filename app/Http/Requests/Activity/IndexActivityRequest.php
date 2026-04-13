<?php

namespace App\Http\Requests\Activity;

use App\Enums\ActivityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Optional filters for listing activities (calendar, student dashboard, subject groups, etc.).
     * When omitted, behaviour matches the previous unfiltered list.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'subject_group_ids' => ['sometimes', 'array'],
            'subject_group_ids.*' => ['integer', 'distinct', 'exists:subject_groups,id'],
            'academic_year_id' => ['sometimes', 'nullable', 'integer', 'exists:academic_years,id'],
            'status' => ['sometimes', 'nullable', Rule::enum(ActivityStatus::class)],
        ];
    }
}
