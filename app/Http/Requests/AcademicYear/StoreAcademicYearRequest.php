<?php

namespace App\Http\Requests\AcademicYear;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicYearRequest extends FormRequest
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
            'year_code' => [
                'required', 'string', 'max:10',
                // Regex per format tipus "2024-2025" o "24-25"
                'regex:/^\d{2,4}-\d{2,4}$/'
            ],
            'description' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Missatges personalitzats (opcional)
     */
    public function messages(): array
    {
        return [
            'year_code.regex' => 'El format del codi d\'any ha de ser tipus "2024-2025".',
            'year_code.unique' => 'Aquest curs acadèmic ja està registrat.',
        ];
    }
}
