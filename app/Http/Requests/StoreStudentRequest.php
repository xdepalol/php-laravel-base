<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'surname1' => ['required', 'string', 'max:100'],
            'surname2' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'string', 'max:100'],
            'birthday_date' => ['date']
        ];
    }
}
