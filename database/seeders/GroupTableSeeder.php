<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Group;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Obtenir les entitats base necessàries
        $courseDaw = Course::where('acronym', 'DAW')->first();
        $academicYear = AcademicYear::where('is_active', true)->first();

        // Verificació de seguretat
        if (!$courseDaw || !$academicYear) {
            $this->command->error("Falten dades base: assegura't que Courses i AcademicYears estiguin seedejats.");
            return;
        }

        // 2. Definició de la configuració dels grups i els seus tutors (per àlies)
        $groupsConfig = [
            [
                'name' => 'DAW1',
                'course_level' => 1,
                'tutor_alias' => 'dherrera',
            ],
            [
                'name' => 'DAW2',
                'course_level' => 2,
                'tutor_alias' => 'xdepalol', // Corregit de 'xepalol' a 'xdepalol' segons converses prèvies
            ],
        ];

        foreach ($groupsConfig as $config) {
            // Busquem l'usuari pel seu àlies
            $user = User::where('alias', $config['tutor_alias'])->first();
            
            $tutorId = null;
            if ($user) {
                $tutorId = $user->id;
                // // Com que la PK de Teacher és el user_id, comprovem si existeix el perfil de professor
                // $teacher = Teacher::find($user->id);
                // if ($teacher) {
                //     $tutorId = $teacher->user_id;
                // } else {
                //     $this->command->warn("L'usuari {$config['tutor_alias']} existeix però no té perfil de Teacher.");
                // }
            } else {
                $this->command->warn("No s'ha trobat l'usuari amb àlies: {$config['tutor_alias']}");
            }

            // 3. Creació del grup
            Group::updateOrCreate(
                [
                    'course_id' => $courseDaw->id,
                    'academic_year_id' => $academicYear->id,
                    'course_level' => $config['course_level'],
                    'name' => $config['name'],
                ],
                [
                    'tutor_id' => $tutorId, // Serà l'ID de l'usuari o null
                ]
            );
        }

        $this->command->info("Grups de DAW1 i DAW2 creats correctament.");
    }
}
