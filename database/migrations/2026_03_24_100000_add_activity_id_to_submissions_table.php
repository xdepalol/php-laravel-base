<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * For databases that created submissions before activity_id existed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('submissions') || Schema::hasColumn('submissions', 'activity_id')) {
            return;
        }

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('activity_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        if (Schema::hasTable('deliverables')) {
            DB::table('submissions')->orderBy('id')->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $activityId = DB::table('deliverables')
                        ->where('id', $row->deliverable_id)
                        ->value('activity_id');
                    if ($activityId !== null) {
                        DB::table('submissions')->where('id', $row->id)->update(['activity_id' => $activityId]);
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
        if (! Schema::hasTable('submissions') || ! Schema::hasColumn('submissions', 'activity_id')) {
            return;
        }

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_id');
        });
    }
};
