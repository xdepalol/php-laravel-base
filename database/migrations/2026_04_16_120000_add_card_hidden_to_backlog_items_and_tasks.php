<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backlog_items', function (Blueprint $table) {
            $table->boolean('card_hidden')->default(false)->after('position');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('card_hidden')->default(false)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('backlog_items', function (Blueprint $table) {
            $table->dropColumn('card_hidden');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('card_hidden');
        });
    }
};
