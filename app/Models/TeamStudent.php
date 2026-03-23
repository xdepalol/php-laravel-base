<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamStudent extends Pivot
{
    protected $table = 'team_student';

    public function activityRole(): BelongsTo
    {
        return $this->belongsTo(ActivityRole::class);
    }
}
