<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AcademicYearsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('academic_years')->delete();
        
        \DB::table('academic_years')->insert(array (
            0 => 
            array (
                'id' => 1,
                'year_code' => '2025-2026',
                'description' => 'Any acadèmic 2025-2026',
                'is_active' => true,
            ),
        ));
 
    }
}
