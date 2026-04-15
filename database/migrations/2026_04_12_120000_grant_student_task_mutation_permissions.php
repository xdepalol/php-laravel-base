<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Students need task-create / task-edit / task-delete for the team backlog UI;
 * ActivityTaskController restricts mutations to shared or own-team backlog items.
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $student = Role::query()->where('name', 'student')->where('guard_name', 'web')->first();
        if (! $student) {
            return;
        }

        foreach (['task-create', 'task-edit', 'task-delete'] as $name) {
            $permission = Permission::query()->where('name', $name)->where('guard_name', 'web')->first();
            if ($permission && ! $student->hasPermissionTo($permission)) {
                $student->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $student = Role::query()->where('name', 'student')->where('guard_name', 'web')->first();
        if (! $student) {
            return;
        }

        foreach (['task-create', 'task-edit', 'task-delete'] as $name) {
            $permission = Permission::query()->where('name', $name)->where('guard_name', 'web')->first();
            if ($permission && $student->hasPermissionTo($permission)) {
                $student->revokePermissionTo($permission);
            }
        }
    }
};
