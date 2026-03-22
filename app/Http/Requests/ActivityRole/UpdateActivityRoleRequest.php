<?php

namespace App\Http\Requests\ActivityRole;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRoleRequest extends FormRequest
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
            'activity_role_type_id' => ['required', 'exists:activity_role_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_mandatory' => ['boolean'],
            'max_per_team' => ['required', 'integer', 'min:1'],
            'position' => ['required', 'integer', 'min:1'],
        ];
    }
}
