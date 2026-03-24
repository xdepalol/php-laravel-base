<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'backlog_item_id',
        'title',
        'description',
        'status',
        'position',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'position' => 'integer',
    ];

    public function backlogItem(): BelongsTo
    {
        return $this->belongsTo(BacklogItem::class);
    }

    public function phaseTasks(): HasMany
    {
        return $this->hasMany(PhaseTask::class);
    }
}
