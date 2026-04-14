<?php

namespace App\Http\Requests\BacklogItem;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportActivityBacklogCsvRequest extends FormRequest
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
        /** @var Activity $activity */
        $activity = $this->route('activity');

        return [
            'csv' => ['required', 'string', 'max:512000'],
            'team_id' => [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where('activity_id', $activity->id),
            ],
        ];
    }
}
