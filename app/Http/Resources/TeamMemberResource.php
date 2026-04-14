<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student row from team_student with pivot (student_id = students.user_id).
 */
class TeamMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pivot = $this->pivot;

        $role = $pivot->activity_role_id === null
            ? null
            : $pivot->activityRole;

        return [
            'student_id' => $this->user_id,
            'activity_role_id' => $pivot->activity_role_id,
            'activity_role' => $role === null ? null : new ActivityRoleResource($role),
            'student' => new StudentResource($this),
        ];
    }
}
