<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            ['acronym' => '0612', 'name' => 'Desenvolupament web en entorn client', 'year_hours' => 99],
            ['acronym' => '0613', 'name' => 'Desenvolupament web en entorn servidor', 'year_hours' => 99],
            ['acronym' => '0614', 'name' => 'Desplegament d’aplicacions web', 'year_hours' => 66],
            ['acronym' => '0615', 'name' => 'Disseny d’interfícies web', 'year_hours' => 66],
        ];

        foreach ($subjects as $subject) {
            \App\Models\Subject::updateOrCreate(
                ['acronym' => $subject['acronym']], // Evita duplicats si tornes a fer seed
                $subject
            );
        }
    }
}
