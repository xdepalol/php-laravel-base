<?php

namespace App\Http\Requests\Submission;

use App\Enums\SubmissionStatus;
use App\Models\Deliverable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()->can('submission-create')) {
            return true;
        }

        return $this->user()->hasRole('student') && ! $this->user()->hasRole('teacher');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $deliverable = $this->route('deliverable');
        if (! $deliverable instanceof Deliverable) {
            $deliverable = Deliverable::query()->findOrFail($this->route('deliverable'));
        }

        if ($this->user()->can('submission-create')) {
            return $this->teacherRules($deliverable);
        }

        return $this->studentRules($deliverable);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    private function teacherRules(Deliverable $deliverable): array
    {
        $activityId = $deliverable->activity_id;

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

    /**
     * Entrega por miembro de equipo: URL y comentarios opcionales; team_id para comprobar pertenencia.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    private function studentRules(Deliverable $deliverable): array
    {
        return [
            'content_url' => ['nullable', 'string', 'max:256'],
            'content_text' => ['nullable', 'string', 'max:65535'],
            'team_id' => [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where('activity_id', $deliverable->activity_id),
            ],
        ];
    }
}
