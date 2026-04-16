<?php

namespace App\Models;

use App\Enums\PhaseTeamSprintStatus;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'activity_id',
        'backlog_item_id',
        'title',
        'description',
        'status',
        'position',
        'card_hidden',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'position' => 'integer',
        'card_hidden' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function backlogItem(): BelongsTo
    {
        return $this->belongsTo(BacklogItem::class);
    }

    public function phaseTasks(): HasMany
    {
        return $this->hasMany(PhaseTask::class);
    }

    /**
     * La tarea está enlazada a una fase sprint cuyo equipo tiene el sprint aún activo (no terminado).
     */
    public function isLinkedToAnyActiveSprint(): bool
    {
        $activeValues = [
            PhaseTeamSprintStatus::ASSIGNING_TASKS->value,
            PhaseTeamSprintStatus::DEVELOPING->value,
            PhaseTeamSprintStatus::REVISING->value,
            PhaseTeamSprintStatus::RETROSPECTIVE->value,
        ];

        return $this->phaseTasks()
            ->whereHas('phase', fn ($q) => $q->where('is_sprint', true))
            ->whereHas('phase.phaseTeams', fn ($q) => $q->whereIn('sprint_status', $activeValues))
            ->exists();
    }
}
