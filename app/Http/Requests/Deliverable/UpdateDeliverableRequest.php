<?php

namespace App\Http\Requests\Deliverable;

use App\Enums\DeliverableStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliverableRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => [Rule::enum(DeliverableStatus::class)],
            'is_group_deliverable' => ['boolean'],
        ];
    }
}
