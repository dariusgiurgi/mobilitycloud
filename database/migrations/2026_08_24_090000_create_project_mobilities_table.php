<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_mobilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('destination_country', 100)->nullable();
            $table->string('host_organisation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        // Preserve the one-mobility setup used by existing projects. The old
        // project columns stay in place for compatibility while the workspace
        // gradually reads the new, repeatable mobility records.
        DB::table('projects')
            ->whereNotNull('mobility_start_date')
            ->orWhereNotNull('mobility_end_date')
            ->orderBy('id')
            ->chunkById(100, function ($projects): void {
                $now = now();

                DB::table('project_mobilities')->insert($projects->map(fn ($project): array => [
                    'project_id' => $project->id,
                    'name' => 'Mobility 1',
                    'start_date' => $project->mobility_start_date,
                    'end_date' => $project->mobility_end_date,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_mobilities');
    }
};
