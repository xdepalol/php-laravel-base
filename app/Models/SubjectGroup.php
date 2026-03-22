<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectGroup extends Model
{
    protected $fillable = ['academic_year_id', 'group_id', 'subject_id'];

    // Relació cap a mestres
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'subject_group_teacher', 'subject_group_id', 'teacher_id')
                    ->withPivot('is_main')
                    ->withTimestamps();
    }
    
    // Altres relacions
    public function group() { return $this->belongsTo(Group::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_subject_group')
            ->withTimestamps();
    }
}

