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

        /* TEACHER */
        // Calculem permisos
        // ReadWrite Access
        $teacherPermissions = [];
        $entities = [
            'activity', 'task', 'deliverable', 'team', 'backlogitem', 'phasetask',
            'phase', 'submission', 'phasestudentrole'
        ];
        $actions = ['list', 'edit', 'create', 'delete', 'view'];
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                // Formatem segons el teu estàndard: 'entitat-accio'
                $teacherPermissions[] = "{$entity}-{$action}";
            }
        }
        // Read access
        $entities = [
            'exercise', 'category', 'course', 'student', 'teacher', 'academicyear', 'subject', 'group', 'subjectgroup', 'enrollment',
            'activityroletype', 'activityrole',
        ];
        $actions = ['list', 'view'];
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                // Formatem segons el teu estàndard: 'entitat-accio'
                $teacherPermissions[] = "{$entity}-{$action}";
            }
        }

        // Actualitzem els rols
        $teacherRole = Role::find(3);
        $teacherRole->syncPermissions($teacherPermissions);

        /* STUDENT */
        // Calculem permisos
        // ReadWrite Access
        $studentPermissions = [];
        $entities = [
            'backlogitem', 'phasetask', 'phasestudentrole'
        ];
        $actions = ['list', 'edit', 'create', 'delete', 'view'];
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                // Formatem segons el teu estàndard: 'entitat-accio'
                $studentPermissions[] = "{$entity}-{$action}";
            }
        }
        // Read access
        $entities = [
            'activity', 'task', 'deliverable', 'team', 'phase', 'submission', 'exercise', 'category', 'course', 'student', 'teacher', 'academicyear', 'subject', 'group', 'subjectgroup', 'enrollment',
            'activityroletype', 'activityrole',
        ];
        $actions = ['list', 'view'];
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                // Formatem segons el teu estàndard: 'entitat-accio'
                $studentPermissions[] = "{$entity}-{$action}";
            }
        }

        // Actualitzem els rols
        $studentRole = Role::find(4);
        $studentRole->syncPermissions($studentPermissions);
        
    }
}