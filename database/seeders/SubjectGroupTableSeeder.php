<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Subject;
use App\Models\SubjectGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectGroupTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Identifiquem el grup DAW2 (id: 2 segons la teva taula)
        $groupDaw2 = Group::find(2);

        if (!$groupDaw2) {
            $this->command->error("No s'ha trobat el grup DAW2 amb ID 2.");
            return;
        }

        // 2. Definició de la configuració: acrònim de l'assignatura => user_id del professor
        $config = [
            'M0612' => 1,
            'M0613' => 4,
            'M0614' => 1,
            'M0615' => 1,
        ];

        foreach ($config as $acronym => $teacherId) {
            // Busquem l'assignatura per l'acrònim
            $subject = Subject::where('acronym', $acronym)->first();

            if ($subject) {
                // Creem el SubjectGroup (o l'actualitzem si ja existeix)
                $subjectGroup = SubjectGroup::updateOrCreate([
                    'academic_year_id' => $groupDaw2->academic_year_id,
                    'group_id'         => $groupDaw2->id,
                    'subject_id'       => $subject->id,
                ]);

                // 3. Assignem el professor a la taula pivot
                // Usem sync() en lloc d'attach() per evitar duplicats si tornes a executar el seeder
                $subjectGroup->teachers()->sync([
                    $teacherId => ['is_main' => true]
                ]);
            } else {
                $this->command->warn("No s'ha trobat l'assignatura amb acrònim: $acronym");
            }
        }
    }
}
