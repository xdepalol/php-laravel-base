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
        Schema::create('subject_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Taula pivot per als professors
        Schema::create('subject_group_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers', 'user_id')->onDelete('cascade');
            $table->boolean('is_main')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_group_teacher');
        Schema::dropIfExists('subject_groups');
    }
};
