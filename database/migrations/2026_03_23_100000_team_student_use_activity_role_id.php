<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * For databases that already ran create_teams_and_team_student_tables with is_scrum_master.
     * Existing pivot rows are removed because they cannot be mapped to activity_role_id.
     */
    public function up(): void
    {
        if (! Schema::hasTable('team_student')) {
            return;
        }

        if (Schema::hasColumn('team_student', 'activity_role_id')) {
            return;
        }

        if (Schema::hasColumn('team_student', 'is_scrum_master')) {
            DB::table('team_student')->delete();
        }

        Schema::table('team_student', function (Blueprint $table) {
            if (Schema::hasColumn('team_student', 'is_scrum_master')) {
                $table->dropColumn('is_scrum_master');
            }
        });

        Schema::table('team_student', function (Blueprint $table) {
            $table->foreignId('activity_role_id')
                ->after('student_id')
                ->constrained('activity_roles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('team_student')) {
            return;
        }

        if (! Schema::hasColumn('team_student', 'activity_role_id')) {
            return;
        }

        Schema::table('team_student', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_role_id');
        });

        Schema::table('team_student', function (Blueprint $table) {
            $table->boolean('is_scrum_master')->default(false);
        });
    }
};
