<?php

namespace App\Models;

use App\Enums\BacklogItemPriority;
use App\Enums\BacklogItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BacklogItem extends Model
{
    protected $fillable = [
        'activity_id',
        'team_id',
        'title',
        'description',
        'priority',
        'points',
        'status',
        'position',
        'card_hidden',
    ];

    protected $casts = [
        'priority' => BacklogItemPriority::class,
        'status' => BacklogItemStatus::class,
        'points' => 'integer',
        'position' => 'integer',
        'card_hidden' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position')->orderBy('id');
    }
}
