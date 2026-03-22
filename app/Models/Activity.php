<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'academic_year_id', 'title', 'description', 'type',
        'activity_role_type_id',
        'has_sprints', 'has_backlog', 'is_intermodular',
        'status', 'start_date', 'end_date',
    ];

    protected $casts = [
        'type' => ActivityType::class,
        'status' => ActivityStatus::class,
        'has_sprints' => 'boolean',
        'has_backlog' => 'boolean',
        'is_intermodular' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function subjectGroups()
    {
        return $this->belongsToMany(SubjectGroup::class, 'activity_subject_group')
            ->withTimestamps();
    }

    public function activityRoleType()
    {
        return $this->belongsTo(ActivityRoleType::class);
    }
}
