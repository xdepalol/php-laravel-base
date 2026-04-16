<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskStatus;
use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityTaskRequest extends FormRequest
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
            'backlog_item_id' => [
                'required',
                Rule::exists('backlog_items', 'id')->where('activity_id', $activityId),
            ],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'position' => ['nullable', 'integer', 'min:0'],
            'card_hidden' => ['sometimes', 'boolean'],
        ];
    }
}
