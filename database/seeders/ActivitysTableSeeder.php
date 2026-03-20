<?php

namespace Database\Seeders;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActivitysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Creem una activitat Scrum intermodular
        $projecte = Activity::create([
            'academic_year_id' => 1,
            'title' => 'Projecte Fullstack',
            'description' => 'Desenvolupament complet d\'una aplicació web.',
            'type' => ActivityType::PROJECT,
            'status' => ActivityStatus::DRAFT,
            'has_sprints' => true,
            'has_backlog' => true,
            'is_intermodular' => true,
            'start_date' => '2026-04-01',
            'end_date' => '2026-05-30',
        ]);

        // La vinculem a M06 (id:1) i M07 (id:2) a través de la pivot
        $projecte->subjectGroups()->attach([1, 2]);

    }
}
