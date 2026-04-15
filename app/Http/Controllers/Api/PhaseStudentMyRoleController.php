<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase\UpdateMyPhaseStudentRoleRequest;
use App\Http\Resources\PhaseStudentRoleResource;
use App\Models\Activity;
use App\Models\ActivityRole;
use App\Models\Phase;
use App\Models\PhaseStudentRole;
use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Resources\Json\JsonResource;

class PhaseStudentMyRoleController extends Controller
{
    /**
     * El usuario autenticado asigna solo su rol en la fase para este equipo.
     */
    public function update(UpdateMyPhaseStudentRoleRequest $request, Activity $activity, Phase $phase, Team $team): JsonResource|\Illuminate\Http\Response
    {
        $this->authorize('team-view');

        abort_unless($phase->activity_id === $activity->id, 404);
        abort_unless($team->activity_id === $activity->id, 404);

        abort_unless($activity->activity_role_type_id, 403, 'La actividad no tiene tipos de rol configurados.');

        $user = $request->user();
        abort_unless($this->userBelongsToTeam($user, $team), 403);

        if (! $this->userMayEditPhaseRole($user, $activity, $phase)) {
            abort(403);
        }

        $studentId = $user->id;
        $activityRoleId = $request->validated('activity_role_id');

        if ($activityRoleId !== null) {
            $ok = ActivityRole::query()
                ->where('id', $activityRoleId)
                ->where('activity_role_type_id', $activity->activity_role_type_id)
                ->exists();
            abort_unless($ok, 422, 'El rol no pertenece al tipo de la actividad.');
        }

        if ($activityRoleId === null) {
            PhaseStudentRole::query()
                ->where('phase_id', $phase->id)
                ->where('team_id', $team->id)
                ->where('student_id', $studentId)
                ->delete();

            return response()->noContent();
        }

        $row = PhaseStudentRole::query()->updateOrCreate(
            [
                'phase_id' => $phase->id,
                'team_id' => $team->id,
                'student_id' => $studentId,
            ],
            ['activity_role_id' => $activityRoleId]
        );
        $row->load(['phase', 'student.user', 'team', 'activityRole']);

        return new PhaseStudentRoleResource($row);
    }

    private function userBelongsToTeam(Authenticatable $user, Team $team): bool
    {
        return $team->students()->where('students.user_id', $user->id)->exists();
    }

    private function userMayEditPhaseRole(Authenticatable $user, Activity $activity, Phase $phase): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('teacher')) {
            return $user->can('phase-edit');
        }

        return (bool) $phase->teams_may_assign_phase_roles;
    }
}
