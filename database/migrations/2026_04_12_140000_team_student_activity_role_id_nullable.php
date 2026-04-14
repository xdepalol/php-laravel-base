<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El rol del miembro en el equipo es opcional hasta que se asigne explícitamente.
     */
    public function up(): void
    {
        if (! Schema::hasTable('team_student') || ! Schema::hasColumn('team_student', 'activity_role_id')) {
            return;
        }

        Schema::table('team_student', function (Blueprint $table) {
            $table->dropForeign(['activity_role_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE team_student MODIFY activity_role_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE team_student ALTER COLUMN activity_role_id DROP NOT NULL');
        } else {
            // sqlite u otros: intento genérico (puede requerir dbal en sqlite antiguo)
            Schema::table('team_student', function (Blueprint $table) {
                $table->unsignedBigInteger('activity_role_id')->nullable()->change();
            });
        }

        Schema::table('team_student', function (Blueprint $table) {
            $table->foreign('activity_role_id')
                ->references('id')
                ->on('activity_roles')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('team_student') || ! Schema::hasColumn('team_student', 'activity_role_id')) {
            return;
        }

        // Forzar NULL a un rol válido no es seguro; solo revertimos el esquema si no hay NULLs.
        $hasNull = DB::table('team_student')->whereNull('activity_role_id')->exists();
        if ($hasNull) {
            throw new \RuntimeException('No se puede revertir: hay filas con activity_role_id NULL. Asigna roles antes.');
        }

        Schema::table('team_student', function (Blueprint $table) {
            $table->dropForeign(['activity_role_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE team_student MODIFY activity_role_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE team_student ALTER COLUMN activity_role_id SET NOT NULL');
        } else {
            Schema::table('team_student', function (Blueprint $table) {
                $table->unsignedBigInteger('activity_role_id')->nullable(false)->change();
            });
        }

        Schema::table('team_student', function (Blueprint $table) {
            $table->foreign('activity_role_id')
                ->references('id')
                ->on('activity_roles')
                ->cascadeOnDelete();
        });
    }
};
