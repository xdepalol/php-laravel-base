<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseStudentRole extends Model
{
    protected $table = 'phase_student_role';

    protected $fillable = [
        'phase_id',
        'student_id',
        'team_id',
        'activity_role_id',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function activityRole(): BelongsTo
    {
        return $this->belongsTo(ActivityRole::class);
    }
}
