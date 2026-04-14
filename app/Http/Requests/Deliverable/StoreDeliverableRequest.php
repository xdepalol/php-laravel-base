<?php

namespace App\Http\Requests\Deliverable;

use App\Enums\DeliverableStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read \App\Models\Activity|null $activity
 */
class StoreDeliverableRequest extends FormRequest
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
        $activity = $this->route('activity');

        return [
            'title' => ['required', 'string', 'max:100'],
            'short_code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/',
                Rule::unique('deliverables', 'short_code')->where(
                    fn ($q) => $q->where('activity_id', $activity?->id)
                ),
            ],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => [Rule::enum(DeliverableStatus::class)],
            'is_group_deliverable' => ['boolean'],
        ];
    }
}
