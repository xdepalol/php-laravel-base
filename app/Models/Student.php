<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'user_id',
        'student_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // public function enrollments()
    // {
    //     return $this->hasMany(Enrollment::class, 'student_id', 'user_id');
    // }
}
