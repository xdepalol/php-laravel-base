<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\SyncTeamStudentsRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\Activity;
use App\Models\Team;

class TeamStudentController extends Controller
{
    /**
     * List students in the team (team_student pivot).
     */
    public function index(Activity $activity, Team $team)
    {
        $this->authorize('team-view');

        $team->loadStudentsForApi();

        return TeamMemberResource::collection($team->students);
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
                'activity_role_id' => $row['activity_role_id'],
            ];
        }

        $team->students()->sync($syncPayload);
        $team->loadStudentsForApi();

        return TeamMemberResource::collection($team->students);
    }
}
