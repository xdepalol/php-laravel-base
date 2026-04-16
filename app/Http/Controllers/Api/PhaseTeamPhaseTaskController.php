<?php

namespace App\Http\Controllers\Api;

use App\Enums\PhaseTeamSprintStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PhaseTaskResource;
use App\Models\Activity;
use App\Models\Phase;
use App\Models\PhaseTask;
use App\Models\PhaseTeam;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhaseTeamPhaseTaskController extends Controller
{
    public function store(Request $request, Activity $activity, Phase $phase, Team $team)
    {
        $this->assertUserMayAccessPhaseTeamApi($request);
        $this->assertPhaseTeamScoped($activity, $phase, $team);
        $this->assertUserMayMutateSprintTasks($request, $team);

        $request->validate([
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,user_id'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        abort_unless($phase->is_sprint, 422, 'Esta fase no es un sprint.');

        $this->assertPhaseTeamIsAssigningTasks($phase, $team);

        $task = Task::query()->with('backlogItem')->findOrFail($request->integer('task_id'));
        if (! $task->backlog_item_id || ! $task->backlogItem) {
            throw ValidationException::withMessages(['task_id' => ['La tarea no tiene ítem de backlog.']]);
        }
        if ((int) $task->backlogItem->team_id !== (int) $team->id) {
            throw ValidationException::withMessages(['task_id' => ['La tarea no pertenece al backlog de este equipo.']]);
        }

        $taskStatus = $task->status instanceof TaskStatus ? $task->status : TaskStatus::tryFrom((int) $task->status) ?? TaskStatus::TODO;
        if (in_array($taskStatus, [TaskStatus::DONE, TaskStatus::CANCELLED], true)) {
            throw ValidationException::withMessages([
                'task_id' => ['No se puede incluir en el sprint una tarea hecha o cancelada.'],
            ]);
        }

        $phaseTask = PhaseTask::query()->firstOrCreate(
            [
                'phase_id' => $phase->id,
                'task_id' => $task->id,
            ],
            [
                'student_id' => $request->input('student_id'),
                'position' => $request->integer('position', 0),
            ]
        );

        if ($request->has('student_id')) {
            $phaseTask->student_id = $request->input('student_id');
        }
        if ($request->has('position')) {
            $phaseTask->position = $request->integer('position');
        }
        $phaseTask->save();

        $phaseTask->load(['phase', 'task.backlogItem', 'student.user']);

        return new PhaseTaskResource($phaseTask);
    }

    public function destroy(Request $request, Activity $activity, Phase $phase, Team $team, PhaseTask $phaseTask)
    {
        $this->assertUserMayAccessPhaseTeamApi($request);
        $this->assertPhaseTeamScoped($activity, $phase, $team);
        $this->assertUserMayMutateSprintTasks($request, $team);

        abort_unless((int) $phaseTask->phase_id === (int) $phase->id, 404);

        abort_unless($phase->is_sprint, 422, 'Esta fase no es un sprint.');

        $this->assertPhaseTeamIsAssigningTasks($phase, $team);

        $phaseTask->loadMissing('task.backlogItem');
        $task = $phaseTask->task;
        abort_unless($task && (int) $task->backlogItem?->team_id === (int) $team->id, 422);

        $phaseTask->delete();

        return response()->noContent();
    }

    private function assertPhaseTeamIsAssigningTasks(Phase $phase, Team $team): void
    {
        $row = PhaseTeam::query()->where('phase_id', $phase->id)->where('team_id', $team->id)->first();
        $status = $row?->sprint_status ?? PhaseTeamSprintStatus::FINISHED;
        abort_unless(
            $status === PhaseTeamSprintStatus::ASSIGNING_TASKS,
            422,
            'Solo se pueden enlazar o quitar tareas del sprint durante el paso «Asignando tareas».'
        );
    }

    private function assertPhaseTeamScoped(Activity $activity, Phase $phase, Team $team): void
    {
        if ((int) $phase->activity_id !== (int) $activity->id || (int) $team->activity_id !== (int) $activity->id) {
            abort(404);
        }
    }

    private function assertUserMayAccessPhaseTeamApi(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 401);
        if ($user->can('phase-edit') || $user->can('phase-view')) {
            return;
        }
        abort(403);
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
}
