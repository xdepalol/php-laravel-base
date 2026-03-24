<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase\StorePhaseRequest;
use App\Http\Requests\Phase\UpdatePhaseRequest;
use App\Http\Resources\PhaseResource;
use App\Models\Activity;
use App\Models\Phase;

class PhaseController extends Controller
{
    /**
     * Display a listing of phases for the activity.
     */
    public function index(Activity $activity)
    {
        $this->authorize('phase-list');

        $phases = $activity->phases()->with(['phaseTasks', 'phaseStudentRoles'])->get();
        $phases->each(fn (Phase $p) => $p->setRelation('activity', $activity));

        return PhaseResource::collection($phases);
    }

    /**
     * Store a newly created phase for the activity.
     */
    public function store(StorePhaseRequest $request, Activity $activity)
    {
        $this->authorize('phase-create');

        $phase = new Phase();
        $phase->activity_id = $activity->id;
        $phase->title = $request->title;
        $phase->is_sprint = $request->boolean('is_sprint');
        $phase->start_date = $request->start_date;
        $phase->end_date = $request->end_date;
        $phase->retro_well = $request->retro_well;
        $phase->retro_bad = $request->retro_bad;
        $phase->retro_improvement = $request->retro_improvement;
        $phase->teacher_feedback = $request->teacher_feedback;
        if ($phase->save()) {
            $phase->load(['activity', 'phaseTasks', 'phaseStudentRoles']);

            return new PhaseResource($phase);
        }
    }

    /**
     * Display the specified phase.
     */
    public function show(Activity $activity, Phase $phase)
    {
        $this->authorize('phase-view');

        $phase->load(['activity', 'phaseTasks', 'phaseStudentRoles']);

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
        if ($phase->save()) {
            $phase->load(['activity', 'phaseTasks', 'phaseStudentRoles']);

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

        $phase->load(['activity', 'phaseTasks', 'phaseStudentRoles']);
        $phase->delete();

        return new PhaseResource($phase);
    }
}
