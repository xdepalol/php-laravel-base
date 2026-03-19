<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'name',
        'acronym',
    ];

    /**
     * Relació amb els grups
     */
    public function groups()
    {
        return $this->hasMany(Group::class);
    }
}
