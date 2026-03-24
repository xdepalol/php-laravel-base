<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tasks') || Schema::hasColumn('tasks', 'activity_id')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('activity_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        if (Schema::hasTable('backlog_items')) {
            DB::table('tasks')->orderBy('id')->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $activityId = DB::table('backlog_items')
                        ->where('id', $row->backlog_item_id)
                        ->value('activity_id');
                    if ($activityId !== null) {
                        DB::table('tasks')->where('id', $row->id)->update(['activity_id' => $activityId]);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tasks') || ! Schema::hasColumn('tasks', 'activity_id')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_id');
        });
    }
};
