<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Student::truncate();
        $faker = Faker::create('es_ES');

        for ($i = 0; $i < 25; $i++) {
            $age = rand(18, 30);

            $name = $faker->firstName();
            $surname1 = $faker->lastName();
            $surname2 = rand(0, 1) ? $faker->lastName() : null;
            
            // Normalitzar (treure accents, minúscules)
            $emailBase = Str::slug($name . '.' . $surname1, '.');
            $email = $emailBase . rand(1, 99) . '@example.com';

            Student::create([
                'name' => $name,
                'surname1' => $surname1,
                'surname2' => $surname2,
                'email' => $email,
                'birthday_date' => now()
                    ->subYears($age)
                    ->subDays(rand(0, 365)),
            ]);
        }
    }
}
