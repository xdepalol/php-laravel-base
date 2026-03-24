<?php

namespace App\Http\Requests\Submission;

use App\Enums\SubmissionStatus;
use App\Models\Deliverable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubmissionRequest extends FormRequest
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
        $deliverable = $this->route('deliverable');
        $activityId = $deliverable instanceof Deliverable
            ? $deliverable->activity_id
            : Deliverable::query()->whereKey($deliverable)->value('activity_id');

        return [
            'student_id' => ['nullable', 'exists:students,user_id'],
            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')->where('activity_id', $activityId),
            ],
            'content_url' => ['nullable', 'string', 'max:256'],
            'content_text' => ['nullable', 'string'],
            'submitted_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(SubmissionStatus::class)],
            'grade' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'feedback' => ['nullable', 'string'],
        ];
    }
}
