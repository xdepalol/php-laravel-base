<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phase extends Model
{
    protected $fillable = [
        'activity_id',
        'title',
        'is_sprint',
        'start_date',
        'end_date',
        'retro_well',
        'retro_bad',
        'retro_improvement',
        'teacher_feedback',
        'teams_may_assign_phase_roles',
    ];

    protected $casts = [
        'is_sprint' => 'boolean',
        'teams_may_assign_phase_roles' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Equipos de la misma actividad que la fase (p. ej. rutas anidadas con scopeBindings).
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'activity_id', 'activity_id');
    }

    public function phaseTasks(): HasMany
    {
        return $this->hasMany(PhaseTask::class);
    }

    public function phaseStudentRoles(): HasMany
    {
        return $this->hasMany(PhaseStudentRole::class);
    }
}
