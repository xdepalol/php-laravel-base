<?php

namespace App\Http\Requests\BacklogItem;

use App\Enums\BacklogItemPriority;
use App\Enums\BacklogItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBacklogItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
