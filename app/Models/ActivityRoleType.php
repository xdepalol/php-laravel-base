<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityRoleType extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function activityRoles(): HasMany
    {
        return $this->hasMany(ActivityRole::class);
    }
}
