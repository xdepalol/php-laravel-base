<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'backlog_item_id' => $this->backlog_item_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => [
                'value' => $this->status->value,
                'name' => $this->status->name,
            ],
            'position' => $this->position,
            'card_hidden' => (bool) $this->card_hidden,
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'backlog_item' => new BacklogItemResource($this->whenLoaded('backlogItem')),
            'phase_tasks' => PhaseTaskResource::collection($this->whenLoaded('phaseTasks')),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
