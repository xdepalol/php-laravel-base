<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Activity;
use App\Models\Team;

class TeamController extends Controller
{
    /**
     * Display a listing of teams for the activity.
     */
    public function index(Activity $activity)
    {
        $this->authorize('team-list');

        $teams = $activity->teams()
            ->with(['students.user'])
            ->withCount([
                'students as students_count',
                'submissions as submissions_delivered_count' => function ($query) {
                    $query->whereIn('status', [
                        SubmissionStatus::SUBMITTED->value,
                        SubmissionStatus::GRADED->value,
                    ]);
                },
            ])
            ->get();
        $teams->each(fn (Team $t) => $t->loadStudentsForApi());

        return TeamResource::collection($teams);
    }

    /**
     * Store a newly created team for the activity.
     */
    public function store(StoreTeamRequest $request, Activity $activity)
    {
        $this->authorize('team-create');

        $team = new Team;
        $team->activity_id = $activity->id;
        $team->name = $request->name;
        if ($team->save()) {
            $team->loadCount([
                'students as students_count',
                'submissions as submissions_delivered_count' => function ($query) {
                    $query->whereIn('status', [
                        SubmissionStatus::SUBMITTED->value,
                        SubmissionStatus::GRADED->value,
                    ]);
                },
            ]);
            $team->loadStudentsForApi();

            return new TeamResource($team);
        }
    }

    /**
     * Display the specified team.
     */
    public function show(Activity $activity, Team $team)
    {
        $this->authorize('team-view');

        $team->load('activity');
        $team->loadCount([
            'students as students_count',
            'submissions as submissions_delivered_count' => function ($query) {
                $query->whereIn('status', [
                    SubmissionStatus::SUBMITTED->value,
                    SubmissionStatus::GRADED->value,
                ]);
            },
        ]);
        $team->loadStudentsForApi();

        return new TeamResource($team);
    }

    /**
     * Update the specified team.
     */
    public function update(UpdateTeamRequest $request, Activity $activity, Team $team)
    {
        $this->authorize('team-edit');

        $team->name = $request->name;
        if ($team->save()) {
            $team->load('activity');
            $team->loadCount([
                'students as students_count',
                'submissions as submissions_delivered_count' => function ($query) {
                    $query->whereIn('status', [
                        SubmissionStatus::SUBMITTED->value,
                        SubmissionStatus::GRADED->value,
                    ]);
                },
            ]);
            $team->loadStudentsForApi();

            return new TeamResource($team);
        }

        return null;
    }

    /**
     * Remove the specified team.
     */
    public function destroy(Activity $activity, Team $team)
    {
        $this->authorize('team-delete');

        $team->load('activity');
        $team->loadStudentsForApi();
        $team->delete();

        return new TeamResource($team);
    }
}
