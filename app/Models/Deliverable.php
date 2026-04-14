<?php

namespace App\Models;

use App\Enums\DeliverableStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deliverable extends Model
{
    protected $fillable = [
        'activity_id',
        'title',
        'short_code',
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

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /** Fecha límite ascendente; sin fecha al final; desempate por id. */
    public function scopeOrderedByDueDateThenId(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderBy('id');
    }
}
