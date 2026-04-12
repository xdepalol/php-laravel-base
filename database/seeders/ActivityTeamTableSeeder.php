<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActivityTeamTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Obtenir l'activitat 1 amb els seus SubjectGroups
        $activity = Activity::with('subjectGroups', 'activityRoleType')->find(1);

        if (!$activity) {
            $this->command->error("No s'ha trobat l'activitat.");
            return;
        }

        $availableRoles = $activity->activityRoleType->activityRoles->sortBy('position')->values();

        // 2. Obtenir tots els alumnes matriculats en aquests mòduls
        // Fem un unique() per si un alumne està en dos mòduls de la mateixa activitat
        $studentIds = Enrollment::whereIn('subject_group_id', $activity->subjectGroups->pluck('id'))
            ->where('status', 1) // Suposant que 1 és 'active'
            ->pluck('student_id')
            ->unique()
            ->shuffle(); // Barregem els alumnes de forma aleatòria

        if ($studentIds->isEmpty()) {
            $this->command->warn("No hi ha alumnes matriculats als mòduls de l'activitat.");
            return;
        }

        // 3. Definir noms per als grups
        $names = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta', 'Iota', 'Kappa'];
        
        // 4. Partir la col·lecció d'alumnes en trossos de 6 (si l'últim queda de 5, ja ens va bé)
        $chunks = $studentIds->chunk(5);

        foreach ($chunks as $index => $chunk) {
            // Si ens quedem sense noms, fem servir el número
            $teamName = $names[$index] ?? "Grup " . ($index + 1);

            $team = Team::create([
                'activity_id' => $activity->id,
                'name' => $teamName,
            ]);

            // 5. Assignar alumnes al grup (activity_role_id queda null de moment)
            $syncPayload = [];
            
            // Assignació intel·ligent de rols
            foreach ($chunk->values() as $studentIndex => $studentId) {
                // Intentem assignar el rol que toca per posició. 
                // Si hi ha més alumnes que rols definits, assignem l'últim (ex: Developer o Cercador)
                $role = $availableRoles[$studentIndex] ?? $availableRoles->last();

                $syncPayload[$studentId] = [
                    'activity_role_id' => $role->id,
                ];
            }
            $team->students()->attach($syncPayload);

            $this->command->info("Creat grup $teamName amb " . $chunk->count() . " alumnes.");
        }
    }
}
