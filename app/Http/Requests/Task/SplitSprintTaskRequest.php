<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class SplitSprintTaskRequest extends FormRequest
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
            'title_part_a' => ['nullable', 'string', 'max:150'],
            'title_part_b' => ['required', 'string', 'max:150'],
        ];
    }
}
