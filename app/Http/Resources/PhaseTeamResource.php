<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhaseTeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phase_id' => $this->phase_id,
            'team_id' => $this->team_id,
            'sprint_status' => [
                'value' => $this->sprint_status->value,
                'name' => $this->sprint_status->name,
            ],
            'retro_well' => $this->retro_well,
            'retro_bad' => $this->retro_bad,
            'retro_improvement' => $this->retro_improvement,
            'teacher_feedback' => $this->teacher_feedback,
            'kanban_snapshot' => $this->kanban_snapshot,
            'team' => new TeamResource($this->whenLoaded('team')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
