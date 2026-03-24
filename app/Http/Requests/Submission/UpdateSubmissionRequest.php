<?php

namespace App\Http\Requests\Submission;

use App\Enums\SubmissionStatus;
use App\Models\Activity;
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
        $activity = $this->route('activity');
        $activityId = $activity instanceof Activity ? $activity->id : $activity;

        return [
            'deliverable_id' => [
                'required',
                Rule::exists('deliverables', 'id')->where('activity_id', $activityId),
            ],
            'student_id' => ['nullable', 'exists:students,user_id'],
            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')->where('activity_id', $activityId),
            ],
            'content_url' => ['nullable', 'string', 'max:256'],
            'content_text' => ['nullable', 'string'],
            'submitted_at' => ['nullable', 'date'],
            'status' => [Rule::enum(SubmissionStatus::class)],
            'grade' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'feedback' => ['nullable', 'string'],
        ];
    }
}
