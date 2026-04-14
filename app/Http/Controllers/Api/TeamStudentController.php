<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\SyncTeamStudentsRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\Activity;
use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class TeamStudentController extends Controller
{
    /**
     * List students in the team (team_student pivot).
     */
    public function index(Request $request, Activity $activity, Team $team)
    {
        $this->authorize('team-view');

        if ($this->shouldLimitTeamsToCurrentStudent($request->user())) {
            $userId = $request->user()->id;
            $belongs = $team->students()->where('students.user_id', $userId)->exists();
            if (! $belongs) {
                abort(403);
            }
        }

        $team->loadStudentsForApi();

        return TeamMemberResource::collection($team->students);
    }

    /**
     * Alumnos sin rol docente: solo pueden listar miembros de equipos en los que están.
     */
    private function shouldLimitTeamsToCurrentStudent(Authenticatable $user): bool
    {
        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole('student') && ! $user->hasRole('teacher');
    }

    /**
     * Replace all students in the team and their pivot rows (full sync).
     */
    public function sync(SyncTeamStudentsRequest $request, Activity $activity, Team $team)
    {
        $this->authorize('team-edit');

        $syncPayload = [];
        foreach ($request->students as $row) {
            $studentId = $row['student_id'];
            $syncPayload[$studentId] = [
                'activity_role_id' => $row['activity_role_id'] ?? null,
            ];
        }

        $team->students()->sync($syncPayload);
        $team->loadStudentsForApi();

        return TeamMemberResource::collection($team->students);
    }
}
