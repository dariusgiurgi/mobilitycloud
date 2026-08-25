<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectReadinessCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectReadinessQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_reuses_preloaded_project_relations_without_extra_queries(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Query-efficient project',
            'status' => 'active',
            'approved_budget' => 20_000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);
        Participant::create([
            'project_id' => $project->id,
            'complete_name' => 'Test Participant',
            'birth_date' => '2000-01-01',
            'email' => 'participant@example.test',
            'phone' => '+40123456789',
        ]);

        $project->load([
            'applicationSections',
            'participants.attachments',
            'participants.mobilities',
            'participants.project',
            'documents',
            'budgetLines.expenses',
            'mobilities',
            'tasks',
        ]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        app(ProjectReadinessCheck::class)->build($project);

        $this->assertSame([], DB::getQueryLog(), 'Readiness queried relations that were already loaded.');
    }
}
