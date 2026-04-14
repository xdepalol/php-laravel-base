<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Código corto único por actividad (p. ej. REQ, FIG, MEM).
     */
    public function up(): void
    {
        Schema::table('deliverables', function (Blueprint $table) {
            $table->string('short_code', 32)->nullable()->after('title');
        });

        foreach (DB::table('deliverables')->orderBy('id')->cursor() as $row) {
            DB::table('deliverables')->where('id', $row->id)->update([
                'short_code' => 'D'.$row->id,
            ]);
        }

        Schema::table('deliverables', function (Blueprint $table) {
            $table->unique(['activity_id', 'short_code'], 'deliverables_activity_short_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliverables', function (Blueprint $table) {
            $table->dropUnique('deliverables_activity_short_code_unique');
            $table->dropColumn('short_code');
        });
    }
};
