<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreActivityTaskRequest;
use App\Http\Requests\Task\UpdateActivityTaskRequest;
use App\Http\Resources\TaskResource;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Task;

class ActivityTaskController extends Controller
{
    /**
     * All tasks for this activity (across backlog items).
     */
    public function index(Activity $activity)
    {
        $this->authorize('task-list');

        $tasks = $activity->tasks()
            ->with(['backlogItem', 'activity', 'phaseTasks.phase', 'phaseTasks.student.user'])
            ->get();
        $tasks->each(fn (Task $t) => $t->setRelation('activity', $activity));

        return TaskResource::collection($tasks);
    }

    public function store(StoreActivityTaskRequest $request, Activity $activity)
    {
        $this->authorize('task-create');

        $task = new Task();
        $task->activity_id = $activity->id;
        $task->backlog_item_id = $request->backlog_item_id;
        $task->title = $request->title;
        $task->description = $request->description;
        $task->status = $request->input('status') ?? TaskStatus::TODO;
        $task->position = $request->position ?? 0;
        if ($task->save()) {
            $task->load(['backlogItem', 'activity', 'phaseTasks.phase', 'phaseTasks.student.user']);
            $task->setRelation('activity', $activity);

            return new TaskResource($task);
        }
    }

    public function show(Activity $activity, Task $task)
    {
        $this->authorize('task-view');

        $task->load(['backlogItem', 'activity', 'phaseTasks.phase', 'phaseTasks.student.user']);
        $task->setRelation('activity', $activity);

        return new TaskResource($task);
    }

    public function update(UpdateActivityTaskRequest $request, Activity $activity, Task $task)
    {
        $this->authorize('task-edit');

        $task->activity_id = $activity->id;
        $task->backlog_item_id = $request->backlog_item_id;
        $task->title = $request->title;
        $task->description = $request->description;
        if ($request->exists('status')) {
            $task->status = $request->input('status') ?? TaskStatus::TODO;
        }
        $task->position = $request->position ?? 0;
        if ($task->save()) {
            $task->load(['backlogItem', 'activity', 'phaseTasks.phase', 'phaseTasks.student.user']);
            $task->setRelation('activity', $activity);

            return new TaskResource($task);
        }

        return null;
    }

    public function destroy(Activity $activity, Task $task)
    {
        $this->authorize('task-delete');

        $task->load(['backlogItem', 'activity', 'phaseTasks.phase', 'phaseTasks.student.user']);
        $task->setRelation('activity', $activity);
        $task->delete();

        return new TaskResource($task);
    }
}
