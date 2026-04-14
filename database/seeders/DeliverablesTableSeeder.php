<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Deliverable;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DeliverablesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Busquem el projecte que hem creat abans per l'ID o el Títol
        $projecte = Activity::where('title', 'Projecte Fullstack')->first();

        if (! $projecte) {
            return;
        }

        $deliverables = [
            [
                'short_code' => 'REQ',
                'title' => 'Requeriments funcionals i Model Relacional',
                'description' => 'Documentació detallada de les User Stories i el diagrama ER de la base de dades.',
                'due_date' => Carbon::parse($projecte->start_date)->addWeeks(1),
                'status' => 1, // Published
                'is_group_deliverable' => true,
            ],
            [
                'short_code' => 'FIG',
                'title' => 'Prototip Figma',
                'description' => 'Disseny de la interfície d’usuari (UI) amb els fluxos de navegació principals.',
                'due_date' => Carbon::parse($projecte->start_date)->addWeeks(2),
                'status' => 1, // Published
                'is_group_deliverable' => true,
            ],
            [
                'short_code' => 'MEM',
                'title' => 'Memòria tècnica del producte final',
                'description' => 'Document final que recull l’arquitectura, les decisions tecnològiques i el manual d’usuari.',
                'due_date' => Carbon::parse($projecte->end_date)->subDays(2),
                'status' => 0, // Draft (encara no visible per l'alumne)
                'is_group_deliverable' => true,
            ],
        ];

        foreach ($deliverables as $data) {
            Deliverable::create(array_merge($data, ['activity_id' => $projecte->id]));
        }

    }
}
