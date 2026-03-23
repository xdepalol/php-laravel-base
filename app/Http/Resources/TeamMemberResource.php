<?php

namespace App\Http\Resources;

use App\Models\TeamStudent;
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
        $roleLoaded = $pivot instanceof TeamStudent
            && $pivot->relationLoaded('activityRole')
            && $pivot->activityRole !== null;

        return [
            'student_id' => $this->user_id,
            'activity_role_id' => $pivot->activity_role_id,
            'activity_role' => $this->when(
                $roleLoaded,
                fn () => new ActivityRoleResource($pivot->activityRole)
            ),
            'student' => new StudentResource($this),
        ];
    }
}
