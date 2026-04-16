<?php

namespace App\Http\Controllers\Api;

use App\Enums\PhaseTeamSprintStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PhaseTeam\UpdatePhaseTeamRequest;
use App\Http\Resources\PhaseTeamResource;
use App\Models\Activity;
use App\Models\Phase;
use App\Models\PhaseTask;
use App\Models\PhaseTeam;
use App\Models\Task;
use App\Models\Team;
use App\Services\PhaseTeamKanbanSnapshotBuilder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PhaseTeamController extends Controller
{
    public function show(Request $request, Activity $activity, Phase $phase, Team $team)
    {
        $this->assertUserMayAccessPhaseTeamApi($request);
        $this->assertPhaseTeamScoped($activity, $phase, $team);
        $this->assertUserMayViewPhaseTeam($request, $team);

        $phaseTeam = PhaseTeam::query()->firstOrCreate(
            [
                'phase_id' => $phase->id,
                'team_id' => $team->id,
            ],
            [
                'sprint_status' => PhaseTeamSprintStatus::FINISHED,
            ]
        );

        $phaseTeam->load('team');

        return new PhaseTeamResource($phaseTeam);
    }

    public function update(UpdatePhaseTeamRequest $request, Activity $activity, Phase $phase, Team $team)
    {
        $this->assertUserMayAccessPhaseTeamApi($request);
        $this->assertPhaseTeamScoped($activity, $phase, $team);
        $this->assertUserMayUpdatePhaseTeam($request, $team);

        $user = $request->user();
        $isTeacher = $user->can('phase-edit');
        $canSetSprintStepFreely = $user->can('phase-sprint-set');

        $phaseTeam = PhaseTeam::query()->firstOrCreate(
            [
                'phase_id' => $phase->id,
                'team_id' => $team->id,
            ],
            [
                'sprint_status' => PhaseTeamSprintStatus::FINISHED,
            ]
        );

        $previousStatus = $phaseTeam->sprint_status;

        if ($request->has('teacher_feedback')) {
            abort_unless($isTeacher, 403, 'Solo el profesorado puede enviar feedback.');
        }

        if (! $isTeacher && $request->hasAny(['retro_well', 'retro_bad', 'retro_improvement'])) {
            abort_unless(
                $previousStatus === PhaseTeamSprintStatus::RETROSPECTIVE,
                422,
                'La retrospectiva solo se puede editar durante el paso «Retrospectiva».'
            );
        }

        if ($request->has('sprint_status') && ! $phase->is_sprint) {
            throw ValidationException::withMessages([
                'sprint_status' => ['Esta fase no es un sprint; no se puede cambiar el estado del sprint.'],
            ]);
        }

        if ($request->has('sprint_status')) {
            $new = PhaseTeamSprintStatus::from((int) $request->input('sprint_status'));

            if (! $canSetSprintStepFreely) {
                if ($new !== $previousStatus->next()) {
                    throw ValidationException::withMessages([
                        'sprint_status' => ['Transición de sprint no válida. Solo se permite avanzar un paso.'],
                    ]);
                }

                if ($new === PhaseTeamSprintStatus::FINISHED && $previousStatus === PhaseTeamSprintStatus::RETROSPECTIVE) {
                    $well = $request->has('retro_well') ? (string) $request->retro_well : (string) ($phaseTeam->retro_well ?? '');
                    $bad = $request->has('retro_bad') ? (string) $request->retro_bad : (string) ($phaseTeam->retro_bad ?? '');
                    $improve = $request->has('retro_improvement') ? (string) $request->retro_improvement : (string) ($phaseTeam->retro_improvement ?? '');
                    if (trim($well) === '' || trim($bad) === '' || trim($improve) === '') {
                        throw ValidationException::withMessages([
                            'sprint_status' => ['Completa la retrospectiva (Keep doing, Stop doing y Start doing) antes de finalizar el sprint.'],
                        ]);
                    }
                }
            }
        }

        DB::transaction(function () use ($request, $phaseTeam, $phase, $team, $previousStatus) {
            if ($request->has('retro_well')) {
                $phaseTeam->retro_well = $request->retro_well;
            }
            if ($request->has('retro_bad')) {
                $phaseTeam->retro_bad = $request->retro_bad;
            }
            if ($request->has('retro_improvement')) {
                $phaseTeam->retro_improvement = $request->retro_improvement;
            }
            if ($request->has('teacher_feedback')) {
                $phaseTeam->teacher_feedback = $request->teacher_feedback;
            }

            if ($request->has('sprint_status')) {
                $new = PhaseTeamSprintStatus::from($request->input('sprint_status'));
                $phaseTeam->sprint_status = $new;

                if ($new === PhaseTeamSprintStatus::FINISHED && $previousStatus !== PhaseTeamSprintStatus::FINISHED) {
                    $phaseTeam->kanban_snapshot = PhaseTeamKanbanSnapshotBuilder::build($phase, $team);

                    $allTaskIds = PhaseTask::query()
                        ->where('phase_id', $phase->id)
                        ->whereHas('task.backlogItem', fn ($b) => $b->where('team_id', $team->id))
                        ->pluck('task_id')
                        ->unique()
                        ->values();

                    $incompleteTaskIds = PhaseTask::query()
                        ->where('phase_id', $phase->id)
                        ->whereHas('task.backlogItem', fn ($b) => $b->where('team_id', $team->id))
                        ->whereHas('task', fn ($q) => $q->whereIn('status', [
                            TaskStatus::TODO->value,
                            TaskStatus::DOING->value,
                        ]))
                        ->pluck('task_id')
                        ->unique()
                        ->values();

                    if ($incompleteTaskIds->isNotEmpty()) {
                        Task::query()
                            ->whereIn('id', $incompleteTaskIds)
                            ->update(['status' => TaskStatus::TODO]);
                    }

                    if ($allTaskIds->isNotEmpty()) {
                        PhaseTask::query()
                            ->where('phase_id', $phase->id)
                            ->whereIn('task_id', $allTaskIds)
                            ->delete();
                    }
                }
            }

            $phaseTeam->save();
        });

        $phaseTeam->refresh()->load('team');

        return new PhaseTeamResource($phaseTeam);
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

    private function assertUserMayViewPhaseTeam(Request $request, Team $team): void
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

    private function assertUserMayUpdatePhaseTeam(Request $request, Team $team): void
    {
        $user = $request->user();
        abort_unless($user, 401);
        if ($user->can('phase-edit')) {
            return;
        }
        if ($user->can('phase-view') && $this->userBelongsToTeam($user, $team)) {
            if ($request->hasAny(['teacher_feedback', 'kanban_snapshot'])) {
                abort(403);
            }

            return;
        }
        abort(403);
    }

    private function userBelongsToTeam(Authenticatable $user, Team $team): bool
    {
        return $team->students()->where('students.user_id', $user->id)->exists();
    }
}
