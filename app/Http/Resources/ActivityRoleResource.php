<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityRoleResource extends JsonResource
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
            'activity_role_type_id' => $this->activity_role_type_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_mandatory' => (bool) $this->is_mandatory,
            'max_per_team' => $this->max_per_team,
            'position' => $this->position,
            'activity_role_type' => new ActivityRoleTypeResource($this->whenLoaded('activityRoleType')),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
