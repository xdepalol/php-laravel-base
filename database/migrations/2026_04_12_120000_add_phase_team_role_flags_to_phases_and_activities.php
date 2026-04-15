<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phases', function (Blueprint $table) {
            $table->boolean('teams_may_assign_phase_roles')->default(false)->after('teacher_feedback');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('students_may_assign_own_team_role')->default(false)->after('has_backlog');
        });
    }

    public function down(): void
    {
        Schema::table('phases', function (Blueprint $table) {
            $table->dropColumn('teams_may_assign_phase_roles');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('students_may_assign_own_team_role');
        });
    }
};
