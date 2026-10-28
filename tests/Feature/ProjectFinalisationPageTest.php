<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectFinalisation;
use App\Jobs\GenerateProjectFinalArchive;
use App\Models\Expense;
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
            ->assertSee('Step 1 · Review checks')
            ->assertSee('not requirements for downloading the archive')
            ->assertSee('Prepare archive')
            ->assertSee('This ZIP may contain sensitive project data')
            ->assertSee('download expires after 24 hours')
            ->assertSee('Project essentials')
            ->assertSee('Evidence & files')
            ->assertSee('Participant feedback')
            ->assertSee('source records selected')
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

    public function test_archive_category_counts_only_civil_conventions_as_agreements(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);
        $line = $project->budgetLines()->firstOrFail();

        foreach ([false, true] as $isConvention) {
            Expense::create([
                'budget_line_id' => $line->id,
                'description' => $isConvention ? 'Facilitator agreement' : 'Train ticket',
                'expense_date' => now()->toDateString(),
                'amount' => 100,
                'currency' => 'EUR',
                'exchange_rate' => 1,
                'amount_eur' => 100,
                'is_civil_convention' => $isConvention,
            ]);
        }

        $this->actingAs($user);

        $categories = Livewire::test(ViewProjectFinalisation::class, ['record' => $project->id])
            ->instance()
            ->archiveCategories();

        $this->assertSame(1, $categories['agreements']['count']);
        $this->assertSame(3, $categories['budget']['count']);
    }

    public function test_contextual_help_follows_archive_state_and_viewer_permissions(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $project = Project::create(['owner_id' => $owner->id, 'name' => 'Export help', 'status' => 'active', 'access_mode' => 'restricted']);
        $project->members()->attach($viewer, ['role' => Project::PROJECT_ROLE_VIEWER]);
        $selection = array_fill_keys(ProjectFinalArchive::CATEGORY_KEYS, true);
        $archive = $project->finalArchives()->create([
            'requested_by' => $owner->id,
            'selection' => $selection,
            'selection_hash' => hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR)),
            'status' => ProjectFinalArchive::STATUS_FAILED,
            'failure_message' => 'A file could not be read.',
            'filename' => 'help-test.zip',
        ]);
        $this->actingAs($viewer);
        Livewire::test(ViewProjectFinalisation::class, ['record' => $project->id])
            ->assertSee('Mobi · Help with this export')
            ->assertSee('Preparation needs attention')
            ->assertSee('Ask the project owner or an editor to retry')
            ->assertSee('A file could not be read.')
            ->assertDontSee('wire:click="requestFinalArchive"', false);

        $this->actingAs($owner);
        $archive->update(['status' => ProjectFinalArchive::STATUS_READY, 'expires_at' => now()->addHour(), 'path' => null]);
        Livewire::test(ViewProjectFinalisation::class, ['record' => $project->id])
            ->assertSee('The prepared file is unavailable')
            ->assertSee('Prepare again')
            ->assertSee('Read in guide (new tab)')
            ->assertDontSee(route('projects.final-archive', [$project, $archive]), false);

        $archive->update(['status' => ProjectFinalArchive::STATUS_QUEUED]);
        $component = Livewire::test(ViewProjectFinalisation::class, ['record' => $project->id])
            ->assertSee('Your ZIP is waiting to start');
        $archive->update(['status' => ProjectFinalArchive::STATUS_EXPIRED]);
        $component->call('pollArchive')->assertSee('The download has expired');
        $component->set('include', array_fill_keys(ProjectFinalArchive::CATEGORY_KEYS, false))
            ->assertSee('Choose content first');
    }
}
