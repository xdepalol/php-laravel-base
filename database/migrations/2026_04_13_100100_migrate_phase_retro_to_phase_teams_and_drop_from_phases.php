<?php

use App\Enums\PhaseTeamSprintStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $phases = DB::table('phases')->get();

        foreach ($phases as $phase) {
            $teamIds = DB::table('teams')
                ->where('activity_id', $phase->activity_id)
                ->pluck('id');

            foreach ($teamIds as $teamId) {
                DB::table('phase_teams')->insert([
                    'phase_id' => $phase->id,
                    'team_id' => $teamId,
                    'sprint_status' => PhaseTeamSprintStatus::FINISHED->value,
                    'retro_well' => $phase->retro_well,
                    'retro_bad' => $phase->retro_bad,
                    'retro_improvement' => $phase->retro_improvement,
                    'teacher_feedback' => $phase->teacher_feedback,
                    'kanban_snapshot' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('phases', function (Blueprint $table) {
            $table->dropColumn([
                'retro_well',
                'retro_bad',
                'retro_improvement',
                'teacher_feedback',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('phases', function (Blueprint $table) {
            $table->text('retro_well')->nullable();
            $table->text('retro_bad')->nullable();
            $table->text('retro_improvement')->nullable();
            $table->text('teacher_feedback')->nullable();
        });

        DB::table('phase_teams')->delete();
    }
};
