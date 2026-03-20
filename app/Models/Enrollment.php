<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = ['student_id', 'subject_group_id', 'status'];

    protected $casts = [
        'status' => EnrollmentStatus::class,
    ];

    public function student() { return $this->belongsTo(Student::class, 'student_id', 'user_id'); }
    public function subjectGroup() { return $this->belongsTo(SubjectGroup::class); }
}
