<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase\StorePhaseRequest;
use App\Http\Requests\Phase\UpdatePhaseRequest;
use App\Http\Resources\PhaseResource;
use App\Models\Activity;
use App\Models\Phase;
use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class PhaseController extends Controller
{
    /**
     * Display a listing of phases for the activity.
     */
    public function index(Request $request, Activity $activity)
    {
        $this->authorize('phase-list');

        $query = $activity->phases()->with('phaseTasks');
        if ($this->shouldLimitPhaseStudentRolesForUser($request->user())) {
            $teamIds = $this->teamIdsForStudentInActivity($request->user(), $activity);
            $query->with([
                'phaseStudentRoles' => fn ($q) => $q->whereIn('team_id', $teamIds)->with($this->phaseStudentRoleNestedRelations()),
            ]);
        } else {
            $query->with([
                'phaseStudentRoles.student.user',
                'phaseStudentRoles.activityRole',
                'phaseStudentRoles.team',
            ]);
        }

        $phases = $query->get();
        $phases->each(fn (Phase $p) => $p->setRelation('activity', $activity));

        return PhaseResource::collection($phases);
    }

    /**
     * Store a newly created phase for the activity.
     */
    public function store(StorePhaseRequest $request, Activity $activity)
    {
        $this->authorize('phase-create');

        $phase = new Phase;
        $phase->activity_id = $activity->id;
        $phase->title = $request->title;
        $phase->is_sprint = $request->boolean('is_sprint');
        $phase->start_date = $request->start_date;
        $phase->end_date = $request->end_date;
        $phase->retro_well = $request->retro_well;
        $phase->retro_bad = $request->retro_bad;
        $phase->retro_improvement = $request->retro_improvement;
        $phase->teacher_feedback = $request->teacher_feedback;
        $phase->teams_may_assign_phase_roles = $request->boolean('teams_may_assign_phase_roles');
        if ($phase->save()) {
            $phase->load([
                'activity',
                'phaseTasks',
                'phaseStudentRoles.student.user',
                'phaseStudentRoles.activityRole',
                'phaseStudentRoles.team',
            ]);

            return new PhaseResource($phase);
        }
    }

    /**
     * Display the specified phase.
     */
    public function show(Request $request, Activity $activity, Phase $phase)
    {
        $this->authorize('phase-view');

        if ($this->shouldLimitPhaseStudentRolesForUser($request->user())) {
            $teamIds = $this->teamIdsForStudentInActivity($request->user(), $activity);
            $phase->load([
                'activity',
                'phaseTasks',
                'phaseStudentRoles' => fn ($q) => $q->whereIn('team_id', $teamIds)->with($this->phaseStudentRoleNestedRelations()),
            ]);
        } else {
            $phase->load([
                'activity',
                'phaseTasks',
                'phaseStudentRoles.student.user',
                'phaseStudentRoles.activityRole',
                'phaseStudentRoles.team',
            ]);
        }

        return new PhaseResource($phase);
    }

    /**
     * Update the specified phase.
     */
    public function update(UpdatePhaseRequest $request, Activity $activity, Phase $phase)
    {
        $this->authorize('phase-edit');

        $phase->title = $request->title;
        $phase->is_sprint = $request->boolean('is_sprint');
        $phase->start_date = $request->start_date;
        $phase->end_date = $request->end_date;
        $phase->retro_well = $request->retro_well;
        $phase->retro_bad = $request->retro_bad;
        $phase->retro_improvement = $request->retro_improvement;
        $phase->teacher_feedback = $request->teacher_feedback;
        $phase->teams_may_assign_phase_roles = $request->boolean('teams_may_assign_phase_roles');
        if ($phase->save()) {
            $phase->load([
                'activity',
                'phaseTasks',
                'phaseStudentRoles.student.user',
                'phaseStudentRoles.activityRole',
                'phaseStudentRoles.team',
            ]);

            return new PhaseResource($phase);
        }

        return null;
    }

    /**
     * Remove the specified phase.
     */
    public function destroy(Activity $activity, Phase $phase)
    {
        $this->authorize('phase-delete');

        $phase->load([
            'activity',
            'phaseTasks',
            'phaseStudentRoles.student.user',
            'phaseStudentRoles.activityRole',
            'phaseStudentRoles.team',
        ]);
        $phase->delete();

        return new PhaseResource($phase);
    }

    private function shouldLimitPhaseStudentRolesForUser(?Authenticatable $user): bool
    {
        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole('student') && ! $user->hasRole('teacher');
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function teamIdsForStudentInActivity(Authenticatable $user, Activity $activity)
    {
        return Team::query()
            ->where('activity_id', $activity->id)
            ->whereHas('students', fn ($q) => $q->where('students.user_id', $user->id))
            ->pluck('id');
    }

    /**
     * @return array<int, string>
     */
    private function phaseStudentRoleNestedRelations(): array
    {
        return ['student.user', 'activityRole', 'team'];
    }
}
