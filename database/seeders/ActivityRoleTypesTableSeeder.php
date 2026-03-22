<?php

namespace Database\Seeders;

use App\Models\ActivityRoleType;
use Illuminate\Database\Seeder;

class ActivityRoleTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Tipus: Metodologia Scrum
        $scrum = ActivityRoleType::create([
            'name' => 'Metodologia Scrum',
            'description' => 'Rols estàndard per a la gestió àgil de projectes.'
        ]);

        $scrum->activityRoles()->createMany([
            [
                'name' => 'Scrum Master',
                'description' => 'Facilitador de l’equip i gestor del procés.',
                'is_mandatory' => true,
                'max_per_team' => 1,
                'position' => 1
            ],
            [
                'name' => 'Product Owner',
                'description' => 'Responsable de maximitzar el valor del producte i gestionar el backlog.',
                'is_mandatory' => true,
                'max_per_team' => 1,
                'position' => 2
            ],
            [
                'name' => 'Developer',
                'description' => 'Membre de l’equip de desenvolupament encarregat de lliurar increments.',
                'is_mandatory' => false,
                'max_per_team' => 6,
                'position' => 3
            ],
        ]);

        // Tipus: Treball Cooperatiu (Genèric)
        $generic = ActivityRoleType::create([
            'name' => 'Treball Cooperatiu',
            'description' => 'Rols transversals per a qualsevol activitat en equip a l’aula.'
        ]);

        $generic->activityRoles()->createMany([
            [
                'name' => 'Portaveu',
                'description' => 'S’encarrega de la comunicació amb el professor i de presentar els resultats.',
                'is_mandatory' => true,
                'max_per_team' => 1,
                'position' => 1
            ],
            [
                'name' => 'Editor / Secretari',
                'description' => 'Responsable de la redacció, format i coherència de la documentació.',
                'is_mandatory' => true,
                'max_per_team' => 1,
                'position' => 2
            ],
            [
                'name' => 'Cercador / Documentalista',
                'description' => 'S’encarrega de filtrar la informació i gestionar les referències.',
                'is_mandatory' => false,
                'max_per_team' => 2,
                'position' => 3
            ],
            [
                'name' => 'Tester / Revisor',
                'description' => 'Revisa que el producte final compleixi amb tots els requeriments abans de l’entrega.',
                'is_mandatory' => false,
                'max_per_team' => 2,
                'position' => 4
            ],
            [
                'name' => 'Gestor de temps',
                'description' => 'Controla el calendari i s’assegura que es compleixin els terminis de les fases.',
                'is_mandatory' => false,
                'max_per_team' => 1,
                'position' => 5
            ],
        ]);

        // Tipus: Producció Plàstica
        $plastica = ActivityRoleType::create([
            'name' => 'Producció Plàstica i Prototipatge',
            'description' => 'Rols orientats a la creació d’objectes físics, maquetes o intervencions artístiques.'
        ]);
        
        $plastica->activityRoles()->createMany([
            [
                'name' => 'Dissenyador',
                'description' => 'Responsable dels plànols, esbossos i la definició conceptual de la peça.',
                'is_mandatory' => true,
                'max_per_team' => 1,
                'position' => 1
            ],
            [
                'name' => 'Gestor de Materials',
                'description' => 'Controla l’estoc, el pressupost i s’encarrega de l’aprovisionament i l’ordre del taller.',
                'is_mandatory' => true,
                'max_per_team' => 1,
                'position' => 2
            ],
            [
                'name' => 'Constructor / Artesà',
                'description' => 'S’encarrega de la manipulació física, muntatge i execució tècnica del projecte.',
                'is_mandatory' => false,
                'max_per_team' => 4,
                'position' => 3
            ],
            [
                'name' => 'Control de Qualitat / Acabats',
                'description' => 'Revisa la pulcritud, la resistència de la peça i els detalls estètics finals.',
                'is_mandatory' => false,
                'max_per_team' => 1,
                'position' => 4
            ],
            [
                'name' => 'Documentalista Gràfic',
                'description' => 'Registra el procés de construcció (fotos/vídeo) per a la memòria del projecte.',
                'is_mandatory' => false,
                'max_per_team' => 1,
                'position' => 5
            ],
        ]);

    }
}
