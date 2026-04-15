<?php

namespace App\Http\Requests\PhaseTeam;

use App\Enums\PhaseTeamSprintStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePhaseTeamRequest extends FormRequest
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
            'sprint_status' => ['sometimes', Rule::enum(PhaseTeamSprintStatus::class)],
            'retro_well' => ['nullable', 'string'],
            'retro_bad' => ['nullable', 'string'],
            'retro_improvement' => ['nullable', 'string'],
            'teacher_feedback' => ['nullable', 'string'],
        ];
    }
}
