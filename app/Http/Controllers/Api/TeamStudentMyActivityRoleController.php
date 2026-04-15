<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\UpdateMyTeamActivityRoleRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\Activity;
use App\Models\ActivityRole;
use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamStudentMyActivityRoleController extends Controller
{
    /**
     * El usuario autenticado actualiza solo su activity_role_id en el pivot del equipo.
     */
    public function update(UpdateMyTeamActivityRoleRequest $request, Activity $activity, Team $team): JsonResource
    {
        $this->authorize('team-view');

        abort_unless($team->activity_id === $activity->id, 404);

        abort_unless($activity->activity_role_type_id, 403, 'La actividad no tiene tipos de rol configurados.');

        $user = $request->user();
        abort_unless($this->userBelongsToTeam($user, $team), 403);

        if (! $this->userMayEditTeamActivityRole($user, $activity)) {
            abort(403);
        }

        $activityRoleId = $request->validated('activity_role_id');

        if ($activityRoleId !== null) {
            $ok = ActivityRole::query()
                ->where('id', $activityRoleId)
                ->where('activity_role_type_id', $activity->activity_role_type_id)
                ->exists();
            abort_unless($ok, 422, 'El rol no pertenece al tipo de la actividad.');
        }

        $team->students()->updateExistingPivot($user->id, [
            'activity_role_id' => $activityRoleId,
        ]);
        $team->loadStudentsForApi();
        $member = $team->students->firstWhere('user_id', $user->id);
        abort_unless($member, 404);

        return new TeamMemberResource($member);
    }

    private function userBelongsToTeam(Authenticatable $user, Team $team): bool
    {
        return $team->students()->where('students.user_id', $user->id)->exists();
    }

    private function userMayEditTeamActivityRole(Authenticatable $user, Activity $activity): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('teacher')) {
            return $user->can('team-edit');
        }

        return (bool) $activity->students_may_assign_own_team_role;
    }
}
