<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectFinalisation;
use App\Jobs\GenerateProjectFinalArchive;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\ProjectFinalArchive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectFinalisationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalisation_uses_compact_archive_groups(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(ViewProjectFinalisation::class, ['record' => $project->id])
            ->assertSee('Prepare the final project archive')
            ->assertSee('Recommended before handover')
            ->assertSee('not requirements for downloading the archive')
            ->assertSee('Prepare archive')
            ->assertSee('This ZIP may contain sensitive project data')
            ->assertSee('removed automatically after 24 hours')
            ->assertSee('Project essentials')
            ->assertSee('Evidence & files')
            ->assertSee('Participant feedback')
            ->assertSee('records included')
            ->call('toggleArchiveCategory', 'application')
            ->assertHasNoErrors();
    }

    public function test_owner_can_queue_one_archive_for_the_current_selection(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(ViewProjectFinalisation::class, ['record' => $project->id])
            ->call('requestFinalArchive')
            ->assertHasNoErrors()
            ->assertSee('Waiting in queue');

        $component->call('requestFinalArchive')->assertHasNoErrors();

        $this->assertDatabaseCount('project_final_archives', 1);
        $this->assertDatabaseHas('project_final_archives', [
            'project_id' => $project->id,
            'requested_by' => $user->id,
            'status' => ProjectFinalArchive::STATUS_QUEUED,
        ]);
        $this->assertTrue(ProjectActivityLog::query()
            ->where('project_id', $project->id)
            ->where('event', 'final_archive_queued')
            ->exists());

        Queue::assertPushed(GenerateProjectFinalArchive::class, 1);
    }
}
