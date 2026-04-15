<?php

namespace App\Models;

use App\Enums\PhaseTeamSprintStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseTeam extends Model
{
    protected $fillable = [
        'phase_id',
        'team_id',
        'sprint_status',
        'retro_well',
        'retro_bad',
        'retro_improvement',
        'teacher_feedback',
        'kanban_snapshot',
    ];

    protected $casts = [
        'sprint_status' => PhaseTeamSprintStatus::class,
        'kanban_snapshot' => 'array',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
