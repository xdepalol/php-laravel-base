<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
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
            'academic_year_id' => $this->academic_year_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => [
                'value' => $this->type->value,
                'name' => $this->type->name,
                'label' => method_exists($this->type, 'label') ? $this->type->label() : $this->type->name,
            ],
            'activity_role_type_id' => $this->activity_role_type_id,
            'activity_role_type' => $this->when(
                $this->relationLoaded('activityRoleType'),
                fn () => $this->activityRoleType
                    ? new ActivityRoleTypeResource($this->activityRoleType)
                    : null
            ),
            'status' => [
                'value' => $this->status->value,
                'name' => $this->status->name,
            ],
            'config' => [
                'has_sprints' => (bool) $this->has_sprints,
                'has_backlog' => (bool) $this->has_backlog,
                'students_may_assign_own_team_role' => (bool) $this->students_may_assign_own_team_role,
                'is_intermodular' => (bool) $this->is_intermodular,
            ],
            'dates' => [
                'start' => $this->start_date?->format('Y-m-d'),
                'end' => $this->end_date?->format('Y-m-d'),
            ],
            'subject_groups' => SubjectGroupResource::collection($this->whenLoaded('subjectGroups')),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
