<?php

namespace App\Models;

use App\Enums\DeliverableStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deliverable extends Model
{
    protected $fillable = [
        'activity_id',
        'title',
        'description',
        'due_date',
        'status',
        'is_group_deliverable',
    ];

    protected $casts = [
        'status' => DeliverableStatus::class,
        'due_date' => 'datetime',
        'is_group_deliverable' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
