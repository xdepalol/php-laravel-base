<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TeachersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('teachers')->delete();
        
        \DB::table('teachers')->insert(array (
            0 => 
            array (
                'user_id' => 1,
                'ss_number' => '32 2854628264',
                'teacher_number' => 'TCH-D4H3S1',
                'created_at' => '2026-03-11 08:02:30',
                'updated_at' => '2026-03-11 08:02:30',
            ),
            1 => 
            array (
                'user_id' => 4,
                'ss_number' => '34 7629862061',
                'teacher_number' => 'TCH-D7P2A9',
                'created_at' => '2026-03-11 08:02:30',
                'updated_at' => '2026-03-11 08:02:30',
            ),
        ));
        
    }
}