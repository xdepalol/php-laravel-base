<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
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
            'course_id' => $this->course_id,
            'academic_year_id' => $this->academic_year_id,
            'tutor_id' => $this->tutor_id,
            'course_level' => $this->course_level,
            'name' => $this->name,
            'course' => new CourseResource($this->whenLoaded('course')),
            'tutor' => new TeacherResource($this->whenLoaded('tutor')),
        ];    
    }
}
