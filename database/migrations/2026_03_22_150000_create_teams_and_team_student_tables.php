<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('team_student', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students', 'user_id')->onDelete('cascade');
            $table->foreignId('activity_role_id')->constrained('activity_roles')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['team_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_student');
        Schema::dropIfExists('teams');
    }
};
