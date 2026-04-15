<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Phase;
use App\Models\Team;
use Illuminate\Support\Carbon;

/**
 * Builds version 1 kanban_snapshot JSON for a closed sprint (self-contained, no live tasks).
 */
class PhaseTeamKanbanSnapshotBuilder
{
    public static function build(Phase $phase, Team $team): array
    {
        $todo = [];
        $doing = [];
        $done = [];

        $query = $phase->phaseTasks()
            ->with(['task.backlogItem', 'student.user'])
            ->whereHas('task.backlogItem', fn ($q) => $q->where('team_id', $team->id))
            ->orderBy('position')
            ->orderBy('id');

        foreach ($query->get() as $phaseTask) {
            $task = $phaseTask->task;
            if (! $task) {
                continue;
            }

            $status = $task->status instanceof TaskStatus ? $task->status : TaskStatus::tryFrom((int) $task->status) ?? TaskStatus::TODO;

            $assignee = null;
            if ($phaseTask->student) {
                $u = $phaseTask->student->user;
                $name = $u?->name ?? $u?->email;
                if ($name) {
                    $assignee = ['display_name' => (string) $name];
                }
            }

            $card = [
                'title' => (string) ($task->title ?? ''),
                'status' => $status->value,
                'assignee' => $assignee,
                'ref' => [
                    'phase_task_id' => $phaseTask->id,
                    'task_id' => $task->id,
                ],
            ];

            match ($status) {
                TaskStatus::TODO => $todo[] = $card,
                TaskStatus::DOING => $doing[] = $card,
                TaskStatus::DONE, TaskStatus::CANCELLED => $done[] = $card,
            };
        }

        return [
            'version' => 1,
            'captured_at' => Carbon::now()->utc()->toIso8601String(),
            'columns' => [
                ['key' => 'todo', 'title' => 'Por hacer', 'cards' => $todo],
                ['key' => 'doing', 'title' => 'En progreso', 'cards' => $doing],
                ['key' => 'done', 'title' => 'Hecha', 'cards' => $done],
            ],
        ];
    }
}
