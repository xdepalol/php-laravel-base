<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'name' => 'Desenvolupament d’Aplicacions Web',
                'acronym' => 'DAW'
            ],
            [
                'name' => 'Desenvolupament d’Aplicacions Multiplataforma',
                'acronym' => 'DAM'
            ],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['acronym' => $course['acronym']], // Clau de cerca
                ['name' => $course['name']]         // Dades a actualitzar/crear
            );
        }

    }
}
