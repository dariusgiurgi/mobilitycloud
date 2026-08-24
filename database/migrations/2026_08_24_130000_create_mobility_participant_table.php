<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobility_participant', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_mobility_id')->constrained('project_mobilities')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('participant');
            $table->string('status')->default('planned');
            $table->timestamps();

            $table->unique(['project_mobility_id', 'participant_id']);
            $table->index(['participant_id', 'status']);
        });

        // Legacy projects did not assign participants to individual trips. When
        // a project has only one mobility, that assignment is unambiguous.
        $now = now();
        DB::table('participants')->orderBy('id')->chunkById(200, function ($participants) use ($now): void {
            foreach ($participants as $participant) {
                $mobilityIds = DB::table('project_mobilities')
                    ->where('project_id', $participant->project_id)
                    ->pluck('id');

                if ($mobilityIds->count() !== 1) {
                    continue;
                }

                DB::table('mobility_participant')->insertOrIgnore([
                    'project_mobility_id' => $mobilityIds->first(),
                    'participant_id' => $participant->id,
                    'role' => $participant->role ?: 'participant',
                    'status' => 'planned',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobility_participant');
    }
};
