<?php

namespace App\Http\Requests\SubjectGroup;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectGroupRequest extends FormRequest
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
            'academic_year_id' => 'required|exists:academic_years,id',
            'group_id' => 'required|exists:groups,id',
            'subject_id' => 'required|exists:subjects,id',
            
            // Validem que 'teachers' sigui un array i que cada ID existeixi a la taula teachers
            'teachers' => 'required|array|min:1',
            'teachers.*' => 'exists:teachers,user_id',
            
            // validar qui és el principal
            'main_teacher_id' => 'nullable|exists:teachers,user_id',
        ];

    }
}
