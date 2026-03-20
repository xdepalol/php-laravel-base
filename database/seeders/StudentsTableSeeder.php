<?php

namespace Database\Seeders;

use App\Http\Resources\SubjectGroupResource;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\Student;
use App\Models\SubjectGroup;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class StudentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Student::truncate();
        $faker = Faker::create('es_ES');
        
        $academicYear = AcademicYear::where('is_active', true)->first();
        $groups = Group::where('academic_year_id', $academicYear->id)->get();
        $groupNames = $groups->pluck('name')->implode(', ');
        $this->command->info("Grups: [$groupNames].");

        // Creem 25 alumnes per grup
        foreach ($groups as $group) {
            // Busquem quines assignatures (SubjectGroups) hi ha per a aquest grup
            $sGroups = SubjectGroup::where('group_id', $group->id)->get();

            for ($i=0; $i < 25; $i++) { 
                $age = rand(18, 30);

                $name = $faker->firstName();
                $surname1 = $faker->lastName();
                $surname2 = $faker->lastName();
                
                // Normalitzar (treure accents, minúscules)
                $emailBase = Str::slug($name . '.' . $surname1, '.');
                $alias = $emailBase . rand(1, 99);
                $email = $alias . '@example.com';

                // Creem l'usuari
                $user = User::create([
                    'name' => $name,
                    'surname1' => $surname1,
                    'surname2' => $surname2,
                    'alias' => $alias,
                    'email' => $email,
                    'birthday_date' => now()
                        ->subYears($age)
                        ->subDays(rand(0, 365)),
                    'password' => bcrypt('alumnat'),
                ]);
                // Creem l'alumne
                // Format: ALU + Any + ID amb 5 dígits (ex: ALU-2026-00085)
                $studentNumber = 'ALU-' . date('Y') . '-' . str_pad($user->id, 5, '0', STR_PAD_LEFT);
                $student = Student::create([
                    'user_id' => $user->id,
                    'student_number' => $studentNumber,
                ]);
                // Fem la matricula de l'alumne
                foreach ($sGroups as $sg) {
                    Enrollment::create([
                        'student_id' => $student->user_id,
                        'subject_group_id' => $sg->id,
                        'status' => 1, // 'enrolled'
                    ]);
                }
            }
        }

    }
}
