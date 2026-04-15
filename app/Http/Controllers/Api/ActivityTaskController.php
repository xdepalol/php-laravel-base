<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\ReorderActivityTasksRequest;
use App\Http\Requests\Task\StoreActivityTaskRequest;
use App\Http\Requests\Task\UpdateActivityTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Task;
use App\Support\ContentSanitizer;
use Illuminate\Support\Facades\DB;

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

        $task = new Task;
        $task->activity_id = $activity->id;
        $task->backlog_item_id = $request->backlog_item_id;
        $task->title = $request->title;
        $task->description = ContentSanitizer::sanitize($request->input('description'));
        $task->status = $request->input('status') ?? TaskStatus::TODO;
        $task->position = $request->position ?? $this->nextTaskPosition($request->backlog_item_id);
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
        $task->description = ContentSanitizer::sanitize($request->input('description'));
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

    /**
     * Persist task order within a backlog item (team workspace drag-and-drop).
     */
    public function reorder(ReorderActivityTasksRequest $request, Activity $activity)
    {
        $this->authorize('task-edit');

        $teamId = (int) $request->validated('team_id');
        $backlogItemId = (int) $request->validated('backlog_item_id');
        $ids = $request->validated('ids');

        $backlogItem = BacklogItem::query()
            ->where('activity_id', $activity->id)
            ->whereKey($backlogItemId)
            ->firstOrFail();

        if ($backlogItem->team_id !== null && (int) $backlogItem->team_id !== $teamId) {
            abort(403, 'Este ítem de backlog no pertenece al equipo indicado.');
        }

        $expectedCount = Task::query()
            ->where('activity_id', $activity->id)
            ->where('backlog_item_id', $backlogItemId)
            ->count();

        if (count($ids) !== $expectedCount) {
            abort(422, 'El orden debe incluir todas las tareas de este ítem de backlog.');
        }

        $incomingSorted = collect($ids)->sort()->values()->all();
        $dbSorted = Task::query()
            ->where('activity_id', $activity->id)
            ->where('backlog_item_id', $backlogItemId)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        if ($incomingSorted !== $dbSorted) {
            abort(422, 'Identificadores de tarea no válidos para este ítem.');
        }

        DB::transaction(function () use ($activity, $backlogItemId, $ids) {
            foreach ($ids as $index => $id) {
                Task::query()
                    ->where('activity_id', $activity->id)
                    ->where('backlog_item_id', $backlogItemId)
                    ->whereKey($id)
                    ->update(['position' => $index]);
            }
        });

        return response()->json(['message' => 'OK']);
    }

    private function nextTaskPosition(int $backlogItemId): int
    {
        $max = (int) Task::query()->where('backlog_item_id', $backlogItemId)->max('position');

        return $max + 1;
    }
}
