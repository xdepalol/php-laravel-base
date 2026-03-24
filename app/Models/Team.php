<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'activity_id',
        'name',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'team_student', 'team_id', 'student_id')
            ->using(TeamStudent::class)
            ->withPivot('activity_role_id')
            ->withTimestamps();
    }

    public function backlogItems(): HasMany
    {
        return $this->hasMany(BacklogItem::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function phaseStudentRoles(): HasMany
    {
        return $this->hasMany(PhaseStudentRole::class);
    }

    /**
     * Eager-load students, users, and each pivot's activity role (avoids N+1 in team member APIs).
     */
    public function loadStudentsForApi(): self
    {
        $this->loadMissing(['students.user']);
        $ids = $this->students->pluck('pivot.activity_role_id')->unique()->filter()->values();
        if ($ids->isEmpty()) {
            return $this;
        }
        $roles = ActivityRole::with('activityRoleType')->whereIn('id', $ids)->get()->keyBy('id');
        foreach ($this->students as $student) {
            $student->pivot->setRelation(
                'activityRole',
                $roles->get($student->pivot->activity_role_id)
            );
        }

        return $this;
    }
}
