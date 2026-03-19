<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleHasPermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        
        /* ADMIN */
        // 1. Busquem el rol d'admin (per ID o per nom, millor per nom si pots)
        $adminRole = Role::find(1); 
        // 2. Obtenim tots els noms de permisos
        $permissions = Permission::all();
        // 3. Sincronitzem (això elimina els anteriors i posa els nous)
        $adminRole->syncPermissions($permissions);

        /* USER */
        $userRole = Role::find(2);
        $userRole->syncPermissions([
            'exercise-edit',
            'exercise-all',
            'exercise-delete',
            'student-list',
            'student-view'
        ]);
        
    }
}