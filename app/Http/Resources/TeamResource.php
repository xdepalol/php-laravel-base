<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'name' => $this->name,
            'students' => TeamMemberResource::collection($this->whenLoaded('students')),
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
