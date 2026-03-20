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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students', 'user_id')->onDelete('cascade');
            $table->foreignId('subject_group_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('status')->default(1); // 1: enrolled
            $table->timestamps();

            // Un alumne no pot estar matriculat dues vegades a la mateixa assignatura-grup
            $table->unique(['student_id', 'subject_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
