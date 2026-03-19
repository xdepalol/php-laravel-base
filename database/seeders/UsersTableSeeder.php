<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('users')->delete();

        \DB::table('users')->insert(array (
            0 =>
            array (
                'id' => 1,
                'name' => 'David',
                'surname1' => 'Herrera',
                'surname2' => NULL,
                'alias' => 'dherrera',
                'email' => 'admin@demo.com',
                'email_verified_at' => NULL,
                'password' => bcrypt('12345678'),
                'remember_token' => NULL,
                'created_at' => '2025-07-25 08:51:49',
                'updated_at' => '2025-07-25 08:51:49',
            ),
            1 =>
            array (
                'id' => 2,
                'name' => 'User',
                'surname1' => 'User',
                'surname2' => NULL,
                'alias' => 'user',
                'email' => 'user@demo.com',
                'email_verified_at' => NULL,
                'password' => bcrypt('12345678'),
                'remember_token' => NULL,
                'created_at' => '2025-07-25 08:51:50',
                'updated_at' => '2025-07-25 08:51:50',
            ),
            2 =>
            array (
                'id' => 3,
                'name' => 'David',
                'surname1' => 'Herrera',
                'surname2' => 'Sánchez',
                'alias' => 'dherrera1',
                'email' => 'deivi7.hs@gmail.com',
                'email_verified_at' => NULL,
                'password' => bcrypt('12345678'),
                'remember_token' => NULL,
                'created_at' => '2025-07-25 08:51:50',
                'updated_at' => '2025-07-25 08:51:50',
            ),
            3 =>
            array (
                'id' => 4,
                'name' => 'Xavier',
                'surname1' => 'de Palol',
                'surname2' => 'Arregyu',
                'alias' => 'xdepalol',
                'email' => 'xdepalol@gmail.com',
                'email_verified_at' => NULL,
                'password' => bcrypt('12345678'),
                'remember_token' => NULL,
                'created_at' => '2025-07-25 08:51:50',
                'updated_at' => '2025-07-25 08:51:50',
            ),
        ));


    }
}
