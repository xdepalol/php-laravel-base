<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectGroupResource extends JsonResource
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
            'academic_year_id' => $this->academic_year_id,
            'group_id' => $this->group_id,
            'subject_id' => $this->subject_id,
            'group' => new GroupResource($this->whenLoaded('group')),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'teachers' => TeacherResource::collection($this->whenLoaded('teachers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
