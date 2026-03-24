<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhaseResource extends JsonResource
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
            'activity_id' => $this->activity_id,
            'title' => $this->title,
            'is_sprint' => (bool) $this->is_sprint,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'retro_well' => $this->retro_well,
            'retro_bad' => $this->retro_bad,
            'retro_improvement' => $this->retro_improvement,
            'teacher_feedback' => $this->teacher_feedback,
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'phase_tasks' => PhaseTaskResource::collection($this->whenLoaded('phaseTasks')),
            'phase_student_roles' => PhaseStudentRoleResource::collection($this->whenLoaded('phaseStudentRoles')),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
