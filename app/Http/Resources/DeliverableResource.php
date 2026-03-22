<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliverableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'activity_id' => $this->activity_id,
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->due_date?->toIso8601String(),
            'status' => [
                'value' => $this->status->value,
                'name' => $this->status->name,
            ],
            'is_group_deliverable' => (bool) $this->is_group_deliverable,
            'activity' => $this->when(
                $this->relationLoaded('activity') && $this->activity,
                fn () => new ActivityResource($this->activity)
            ),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
