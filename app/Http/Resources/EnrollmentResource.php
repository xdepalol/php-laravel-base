<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
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
            'student_id' => $this->student_id,
            'subject_group_id' => $this->subject_group_id,
            'status' => [
                'value' => $this->status->value,
                'name' => $this->status->name,
                'label' => method_exists($this->status, 'label') ? $this->status->label() : $this->status->name,
            ],
            'student' => new StudentResource($this->whenLoaded('student')),
            'subject_group' => new SubjectGroupResource($this->whenLoaded('subjectGroup')),
            'created_at' => $this->created_at?->toDateString(),
            'updated_at' => $this->updated_at?->toDateString(),
        ];
    }
}
