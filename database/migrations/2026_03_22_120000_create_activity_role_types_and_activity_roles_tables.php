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
        Schema::create('activity_role_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_role_type_id')->constrained('activity_role_types')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->unsignedInteger('max_per_team')->default(1);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_roles');
        Schema::dropIfExists('activity_role_types');
    }
};
