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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->onDelete('cascade');
            $table->foreignId('deliverable_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained('students', 'user_id')->onDelete('set null');
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->string('content_url', 256)->nullable();
            $table->text('content_text')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->decimal('grade', 4, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
