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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->string('title', 100);
            $table->text('description')->nullable();
            
            // Configuració de l'activitat
            $table->unsignedTinyInteger('type')->default(0); // 0: general
            $table->boolean('has_sprints')->default(false);
            $table->boolean('has_backlog')->default(false);
            $table->boolean('is_intermodular')->default(false);
            $table->unsignedTinyInteger('status')->default(0); // 0: draft
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        // CreateActivitySubjectGroupTable (Pivot)
        Schema::create('activity_subject_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_group_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_subject_group');
        Schema::dropIfExists('activities');
    }
};
