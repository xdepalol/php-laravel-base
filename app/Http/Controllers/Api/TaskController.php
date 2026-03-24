<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;

class TaskController extends Controller
{
    public function index()
    {
        $this->authorize('task-list');

        $tasks = Task::with(['backlogItem', 'phaseTasks.phase', 'phaseTasks.student.user'])->get();

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request)
    {
        $this->authorize('task-create');

        $task = new Task();
        $task->backlog_item_id = $request->backlog_item_id;
        $task->title = $request->title;
        $task->description = $request->description;
        $task->status = $request->status;
        $task->position = $request->position ?? 0;
        if ($task->save()) {
            $task->load(['backlogItem', 'phaseTasks.phase', 'phaseTasks.student.user']);

            return new TaskResource($task);
        }
    }

    public function show(Task $task)
    {
        $this->authorize('task-view');

        $task->load(['backlogItem', 'phaseTasks.phase', 'phaseTasks.student.user']);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->authorize('task-edit');

        $task->backlog_item_id = $request->backlog_item_id;
        $task->title = $request->title;
        $task->description = $request->description;
        $task->status = $request->status;
        $task->position = $request->position ?? 0;
        if ($task->save()) {
            $task->load(['backlogItem', 'phaseTasks.phase', 'phaseTasks.student.user']);

            return new TaskResource($task);
        }

        return null;
    }

    public function destroy(Task $task)
    {
        $this->authorize('task-delete');

        $task->load(['backlogItem', 'phaseTasks.phase', 'phaseTasks.student.user']);
        $task->delete();

        return new TaskResource($task);
    }
}
