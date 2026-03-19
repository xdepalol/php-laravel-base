<?php

namespace App\Http\Requests\Subject;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubjectRequest extends FormRequest
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
        // Captura l'ID del subjecte si estem en una actualització (ruta: /subjects/{subject})
        $subjectId = $this->route('subject')?->id ?? $this->route('subject');
        return [
                'name' => [
                    'required',
                    'string',
                    'min:3',
                    'max:100',
                ],
                'acronym' => [
                    'required',
                    'string',
                    'max:20',
                    // Valida que sigui únic, però ignora l'ID actual si estem editant
                    'unique:subjects,acronym,' . $subjectId,
                ],
                'year_hours' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:500', // Un límit raonable per a hores anuals
                ],
        ];
    }

    /**
     * Personalització dels noms dels atributs per als missatges d'error
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom de l\'assignatura',
            'acronym' => 'codi',
            'year_hours' => 'hores anuals',
        ];
    }

    /**
     * Personalització dels missatges d'error
     */
    public function messages(): array
    {
        return [
            'acronym.unique' => 'Aquest codi d\'assignatura ja està registrat.',
            'year_hours.integer' => 'Les hores han de ser un valor numèric.',
        ];
    }
}
