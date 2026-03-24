<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhaseTaskResource extends JsonResource
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
            'phase_id' => $this->phase_id,
            'task_id' => $this->task_id,
            'student_id' => $this->student_id,
            'position' => $this->position,
            'phase' => new PhaseResource($this->whenLoaded('phase')),
            'task' => new TaskResource($this->whenLoaded('task')),
            'student' => $this->when(
                $this->relationLoaded('student') && $this->student,
                fn () => new StudentResource($this->student)
            ),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
