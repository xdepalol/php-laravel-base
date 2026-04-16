<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BacklogItemResource extends JsonResource
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
            'team_id' => $this->team_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => [
                'value' => $this->priority->value,
                'name' => $this->priority->name,
            ],
            'points' => $this->points,
            'status' => [
                'value' => $this->status->value,
                'name' => $this->status->name,
            ],
            'position' => $this->position,
            'card_hidden' => (bool) $this->card_hidden,
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'team' => $this->when(
                $this->relationLoaded('team') && $this->team,
                fn () => new TeamResource($this->team)
            ),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
