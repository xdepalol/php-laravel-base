<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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
        // Captura l'ID del curs si estem en una actualització (ruta: /courses/{course})
        $courseId = $this->route('course')?->id ?? $this->route('course');
        return [
            'name' => [
                'required',
                'string',
                'min:10',
                'max:100',
            ],
            'acronym' => [
                'required',
                'string',
                'min:2',
                'max:20',
                // Comprova que sigui únic a la taula 'courses', camp 'acronym'
                'unique:courses,acronym,' . $courseId,
            ],
        ];
    }

    /**
     * Personalització dels missatges d'error
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nom del cicle és obligatori.',
            'acronym.required' => 'L’acrònim (DAW, DAM...) és obligatori.',
            'acronym.unique' => 'Aquest acrònim ja està registrat per a un altre cicle.',
        ];
    }
}
