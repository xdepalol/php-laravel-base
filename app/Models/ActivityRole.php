<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityRole extends Model
{
    protected $fillable = [
        'activity_role_type_id',
        'name',
        'description',
        'is_mandatory',
        'max_per_team',
        'position',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'max_per_team' => 'integer',
        'position' => 'integer',
    ];

    public function activityRoleType(): BelongsTo
    {
        return $this->belongsTo(ActivityRoleType::class);
    }

    public function phaseStudentRoles(): HasMany
    {
        return $this->hasMany(PhaseStudentRole::class);
    }
}
