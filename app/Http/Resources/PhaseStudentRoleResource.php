<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhaseStudentRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phase_id' => $this->phase_id,
            'student_id' => $this->student_id,
            'team_id' => $this->team_id,
            'activity_role_id' => $this->activity_role_id,
            'phase' => new PhaseResource($this->whenLoaded('phase')),
            'student' => new StudentResource($this->whenLoaded('student')),
            'team' => new TeamResource($this->whenLoaded('team')),
            'activity_role' => new ActivityRoleResource($this->whenLoaded('activityRole')),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
