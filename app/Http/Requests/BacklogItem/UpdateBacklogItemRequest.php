<?php

namespace App\Http\Requests\BacklogItem;

use App\Enums\BacklogItemPriority;
use App\Enums\BacklogItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBacklogItemRequest extends FormRequest
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
            'activity_id' => ['required', 'exists:activities,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'priority' => [Rule::enum(BacklogItemPriority::class)],
            'points' => ['nullable', 'integer', 'min:0'],
            'status' => [Rule::enum(BacklogItemStatus::class)],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
