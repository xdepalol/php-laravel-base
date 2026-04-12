<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('roles')->delete();

        $roles = [
            'admin', 'user', 'teacher', 'student',
        ];

        $lastId = 0;
        foreach ($roles as $roleName) {
            $lastId = $lastId + 1;
            Role::firstOrCreate([
                'id' => $lastId,
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }        
    }
}