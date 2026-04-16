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
use App\Models\Team;
use App\Support\ContentSanitizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
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

        $backlogItem = BacklogItem::query()
            ->where('activity_id', $activity->id)
            ->whereKey($request->backlog_item_id)
            ->firstOrFail();

        if ($this->actingAsStudentOnly($request->user())) {
            $this->assertStudentMayMutateTasksOnBacklogItem($request->user(), $activity, $backlogItem);
        }

        $task = new Task;
        $task->activity_id = $activity->id;
        $task->backlog_item_id = $request->backlog_item_id;
        $task->title = $request->title;
        $task->description = ContentSanitizer::sanitize($request->input('description'));
        $task->status = $this->actingAsStudentOnly($request->user())
            ? TaskStatus::TODO
            : TaskStatus::from((int) ($request->input('status') ?? TaskStatus::TODO->value));
        $task->position = $request->position ?? $this->nextTaskPosition($request->backlog_item_id);
        $task->card_hidden = $request->boolean('card_hidden');
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

        $task->loadMissing('backlogItem');
        $backlogItem = $task->backlogItem;
        if ($this->actingAsStudentOnly($request->user())) {
            if (! $backlogItem || (int) $backlogItem->activity_id !== (int) $activity->id) {
                abort(403);
            }
            $this->assertStudentMayMutateTasksOnBacklogItem($request->user(), $activity, $backlogItem);
            $request->merge(['backlog_item_id' => $task->backlog_item_id]);
        }

        $task->activity_id = $activity->id;
        $task->backlog_item_id = $request->backlog_item_id;
        $task->title = $request->title;
        $task->description = ContentSanitizer::sanitize($request->input('description'));
        if ($request->exists('status')) {
            $newStatus = TaskStatus::from((int) $request->input('status'));
            if ($this->actingAsStudentOnly($request->user())) {
                $this->assertStudentMayChangeTaskStatusTo($task, $newStatus);
            }
            $task->status = $newStatus;
        }
        $task->position = $request->position ?? 0;
        if ($request->has('card_hidden')) {
            $task->card_hidden = $request->boolean('card_hidden');
        }
        if ($task->save()) {
            $task->load(['backlogItem', 'activity', 'phaseTasks.phase', 'phaseTasks.student.user']);
            $task->setRelation('activity', $activity);

            return new TaskResource($task);
        }

        return null;
    }

    public function destroy(Request $request, Activity $activity, Task $task)
    {
        $this->authorize('task-delete');

        $task->loadMissing('backlogItem');
        $backlogItem = $task->backlogItem;
        if ($this->actingAsStudentOnly($request->user())) {
            if (! $backlogItem || (int) $backlogItem->activity_id !== (int) $activity->id) {
                abort(403);
            }
            $this->assertStudentMayMutateTasksOnBacklogItem($request->user(), $activity, $backlogItem);
        }

        if ($task->isLinkedToAnyActiveSprint()) {
            abort(422, 'No se puede eliminar una tarea incluida en un sprint activo.');
        }

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

        if ($this->actingAsStudentOnly($request->user())) {
            $this->assertStudentBelongsToTeam($request->user(), $activity, $teamId);
            $this->assertStudentMayMutateTasksOnBacklogItem($request->user(), $activity, $backlogItem);
        }

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

    private function actingAsStudentOnly(?Authenticatable $user): bool
    {
        if (! is_object($user) || ! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole('student') && ! $user->hasRole('teacher');
    }

    /**
     * Alumnado: el estado solo se ajusta en el tablero Kanban del sprint (tarea en sprint activo) o en el backlog
     * para cancelar una tarea en cola o restaurar una cancelada.
     */
    private function assertStudentMayChangeTaskStatusTo(Task $task, TaskStatus $new): void
    {
        if ($task->status === $new) {
            return;
        }

        if ($task->isLinkedToAnyActiveSprint()) {
            if (in_array($new, [TaskStatus::TODO, TaskStatus::DOING, TaskStatus::DONE], true)) {
                return;
            }
            abort(422, 'Estado no válido para una tarea en sprint activo.');
        }

        if ($task->status === TaskStatus::TODO && $new === TaskStatus::CANCELLED) {
            return;
        }

        if ($task->status === TaskStatus::CANCELLED && $new === TaskStatus::TODO) {
            return;
        }

        abort(422, 'Desde el backlog solo podés cancelar una tarea en cola o restaurar una cancelada. El resto del estado se cambia en el tablero del sprint.');
    }

    /**
     * Alumnos solo pueden crear/editar/reordenar tareas en ítems compartidos o de un equipo en el que estén.
     */
    private function assertStudentMayMutateTasksOnBacklogItem(Authenticatable $user, Activity $activity, BacklogItem $backlogItem): void
    {
        if ($backlogItem->team_id === null) {
            $this->assertStudentBelongsToAnyTeamOfActivity($user, $activity);

            return;
        }

        $this->assertStudentBelongsToTeam($user, $activity, (int) $backlogItem->team_id);
    }

    private function assertStudentBelongsToAnyTeamOfActivity(Authenticatable $user, Activity $activity): void
    {
        $ok = Team::query()
            ->where('activity_id', $activity->id)
            ->whereHas('students', function ($q) use ($user) {
                $q->where('students.user_id', $user->id);
            })
            ->exists();

        if (! $ok) {
            abort(403, 'No pertenecés a ningún equipo de esta actividad.');
        }
    }

    private function assertStudentBelongsToTeam(Authenticatable $user, Activity $activity, int $teamId): void
    {
        $ok = Team::query()
            ->where('activity_id', $activity->id)
            ->whereKey($teamId)
            ->whereHas('students', function ($q) use ($user) {
                $q->where('students.user_id', $user->id);
            })
            ->exists();

        if (! $ok) {
            abort(403, 'No pertenecés a este equipo en esta actividad.');
        }
    }
}
