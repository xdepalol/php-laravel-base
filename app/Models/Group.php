<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Group extends Model
{
    protected $fillable = [
        'course_id',
        'academic_year_id',
        'tutor_id',
        'course_level',
        'name',
    ];

    /**
     * Relació cap al tutor del grup
     */
    public function tutor()
    {
        return $this->belongsTo(Teacher::class, 'tutor_id', 'user_id');
    }

    public function academic_year()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
