<?php

namespace App\Http\Requests\BacklogItem;

use App\Enums\BacklogItemPriority;
use App\Enums\BacklogItemStatus;
use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityBacklogItemRequest extends FormRequest
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
            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')->where('activity_id', $activityId),
            ],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'priority' => [Rule::enum(BacklogItemPriority::class)],
            'points' => ['nullable', 'integer', 'min:0'],
            'status' => [Rule::enum(BacklogItemStatus::class)],
            'position' => ['nullable', 'integer', 'min:0'],
            'card_hidden' => ['sometimes', 'boolean'],
        ];
    }
}
