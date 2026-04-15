<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('phases')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->unsignedTinyInteger('sprint_status')->default(4);
            $table->text('retro_well')->nullable();
            $table->text('retro_bad')->nullable();
            $table->text('retro_improvement')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->json('kanban_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['phase_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_teams');
    }
};
