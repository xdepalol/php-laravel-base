<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $entities = [
            'role', 'permission', 'user', 'post', 'exercise',
            'category', 'task', 'course', 'student', 'teacher', 'academicyear',
            'subject', 'group', 'subjectgroup', 'enrollment', 'activity',
            'activityroletype', 'activityrole', 'deliverable', 'team',
            'backlogitem', 'phasetask', 'phase', 'submission', 'phasestudentrole',
        ];

        $actions = ['list', 'edit', 'create', 'delete', 'view'];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$entity}-{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        $adhoc_permissions = ['exercise-all', 'academicyear-switch', 'own-enrollments'];
        foreach ($adhoc_permissions as $adhoc_permission) {
            Permission::firstOrCreate([
                'name' => $adhoc_permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
