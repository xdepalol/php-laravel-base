<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhaseTask\StorePhaseTaskRequest;
use App\Http\Requests\PhaseTask\UpdatePhaseTaskRequest;
use App\Http\Resources\PhaseTaskResource;
use App\Models\PhaseTask;

class PhaseTaskController extends Controller
{
    public function index()
    {
        $this->authorize('phasetask-list');

        $phaseTasks = PhaseTask::with(['phase', 'task', 'student.user'])->get();

        return PhaseTaskResource::collection($phaseTasks);
    }

    public function store(StorePhaseTaskRequest $request)
    {
        $this->authorize('phasetask-create');

        $phaseTask = new PhaseTask();
        $phaseTask->phase_id = $request->phase_id;
        $phaseTask->task_id = $request->task_id;
        $phaseTask->student_id = $request->student_id;
        $phaseTask->position = $request->position ?? 0;
        if ($phaseTask->save()) {
            $phaseTask->load(['phase', 'task', 'student.user']);

            return new PhaseTaskResource($phaseTask);
        }
    }

    public function show(PhaseTask $phaseTask)
    {
        $this->authorize('phasetask-view');

        $phaseTask->load(['phase', 'task', 'student.user']);

        return new PhaseTaskResource($phaseTask);
    }

    public function update(UpdatePhaseTaskRequest $request, PhaseTask $phaseTask)
    {
        $this->authorize('phasetask-edit');

        $phaseTask->phase_id = $request->phase_id;
        $phaseTask->task_id = $request->task_id;
        $phaseTask->student_id = $request->student_id;
        $phaseTask->position = $request->position ?? 0;
        if ($phaseTask->save()) {
            $phaseTask->load(['phase', 'task', 'student.user']);

            return new PhaseTaskResource($phaseTask);
        }

        return null;
    }

    public function destroy(PhaseTask $phaseTask)
    {
        $this->authorize('phasetask-delete');

        $phaseTask->load(['phase', 'task', 'student.user']);
        $phaseTask->delete();

        return new PhaseTaskResource($phaseTask);
    }
}
