<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
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
            'deliverable_id' => $this->deliverable_id,
            'student_id' => $this->student_id,
            'team_id' => $this->team_id,
            'content_url' => $this->content_url,
            'content_text' => $this->content_text,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'status' => [
                'value' => $this->status->value,
                'name' => $this->status->name,
            ],
            'grade' => $this->grade,
            'feedback' => $this->feedback,
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'deliverable' => new DeliverableResource($this->whenLoaded('deliverable')),
            'student' => $this->when(
                $this->relationLoaded('student') && $this->student,
                fn () => new StudentResource($this->student)
            ),
            'team' => $this->when(
                $this->relationLoaded('team') && $this->team,
                fn () => new TeamResource($this->team)
            ),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
