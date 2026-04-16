<?php

namespace App\Http\Controllers\Api;

use App\Enums\PhaseTeamSprintStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\SplitSprintTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Phase;
use App\Models\PhaseTask;
use App\Models\PhaseTeam;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivitySprintTaskSplitController extends Controller
{
    public function store(SplitSprintTaskRequest $request, Activity $activity, Phase $phase, Team $team, Task $task)
    {
        /** Alta de la segunda parte + ajuste del título: mismo permiso que editar tareas del sprint. */
        $this->authorize('task-edit');

        $this->assertScoped($activity, $phase, $team, $task);
        $this->assertUserMayMutateSprintTasks($request, $team);
        $this->assertSprintPhaseActiveForTeam($phase, $team);
        $this->assertTaskBelongsToTeamBacklog($task, $team);
        $this->assertTaskInSprintPhase($phase, $task);

        $task->loadMissing('backlogItem');
        if ($this->actingAsStudentOnly($request->user())) {
            $this->assertStudentMayMutateTasksOnBacklogItem($request->user(), $activity, $task->backlogItem);
        }

        $validated = $request->validated();
        $titleB = trim($validated['title_part_b']);
        $titleA = isset($validated['title_part_a']) ? trim((string) $validated['title_part_a']) : '';

        if ($titleB === '') {
            throw ValidationException::withMessages([
                'title_part_b' => ['El título de la nueva parte es obligatorio.'],
            ]);
        }

        $newTask = null;

        DB::transaction(function () use ($activity, $phase, $task, $titleA, $titleB, &$newTask) {
            if ($titleA !== '') {
                $task->title = mb_substr($titleA, 0, 150);
                $task->save();
            }

            $nextTaskPos = $this->nextTaskPosition($task->backlog_item_id);

            $newTask = Task::query()->create([
                'activity_id' => $activity->id,
                'backlog_item_id' => $task->backlog_item_id,
                'title' => mb_substr($titleB, 0, 150),
                'description' => null,
                'status' => TaskStatus::TODO,
                'position' => $nextTaskPos,
                'card_hidden' => false,
            ]);

            $maxPhasePos = (int) PhaseTask::query()->where('phase_id', $phase->id)->max('position');

            PhaseTask::query()->create([
                'phase_id' => $phase->id,
                'task_id' => $newTask->id,
                'student_id' => null,
                'position' => $maxPhasePos + 1,
            ]);

            $task->refresh();
            $newTask->refresh();
            $newTask->load(['backlogItem', 'activity', 'phaseTasks.phase', 'phaseTasks.student.user']);
            $newTask->setRelation('activity', $activity);
            $task->load(['backlogItem', 'activity', 'phaseTasks.phase', 'phaseTasks.student.user']);
            $task->setRelation('activity', $activity);
        });

        return response()->json([
            'message' => 'Tarea dividida.',
            'task_original' => new TaskResource($task),
            'task_new' => new TaskResource($newTask),
        ], 201);
    }

    private function assertScoped(Activity $activity, Phase $phase, Team $team, Task $task): void
    {
        if ((int) $phase->activity_id !== (int) $activity->id
            || (int) $team->activity_id !== (int) $activity->id
            || (int) $task->activity_id !== (int) $activity->id) {
            abort(404);
        }

        abort_unless($phase->is_sprint, 422, 'Esta fase no es un sprint.');
    }

    private function assertSprintPhaseActiveForTeam(Phase $phase, Team $team): void
    {
        $row = PhaseTeam::query()->where('phase_id', $phase->id)->where('team_id', $team->id)->first();
        $status = $row?->sprint_status ?? PhaseTeamSprintStatus::FINISHED;
        $v = $status instanceof PhaseTeamSprintStatus ? $status->value : (int) $status;

        if ($v < 0 || $v > 3) {
            abort(422, 'El sprint de este equipo no está activo en esta fase.');
        }
    }

    private function assertTaskBelongsToTeamBacklog(Task $task, Team $team): void
    {
        $task->loadMissing('backlogItem');
        $bi = $task->backlogItem;
        if (! $bi || (int) $bi->team_id !== (int) $team->id) {
            abort(422, 'La tarea no pertenece al backlog de este equipo.');
        }
    }

    private function assertTaskInSprintPhase(Phase $phase, Task $task): void
    {
        $exists = PhaseTask::query()
            ->where('phase_id', $phase->id)
            ->where('task_id', $task->id)
            ->exists();

        if (! $exists) {
            abort(422, 'La tarea no está incluida en este sprint.');
        }
    }

    private function assertUserMayMutateSprintTasks(Request $request, Team $team): void
    {
        $user = $request->user();
        abort_unless($user, 401);
        if ($user->can('phase-edit')) {
            return;
        }
        if ($user->can('phase-view') && $this->userBelongsToTeam($user, $team)) {
            return;
        }
        abort(403);
    }

    private function userBelongsToTeam(Authenticatable $user, Team $team): bool
    {
        return $team->students()->where('students.user_id', $user->id)->exists();
    }

    private function actingAsStudentOnly(?Authenticatable $user): bool
    {
        if (! is_object($user) || ! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole('student') && ! $user->hasRole('teacher');
    }

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

    private function nextTaskPosition(int $backlogItemId): int
    {
        $max = Task::query()->where('backlog_item_id', $backlogItemId)->max('position');

        return (int) $max + 1;
    }
}
