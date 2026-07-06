<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_surfaces_the_next_step_and_module_readiness(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        ProjectApplicationSection::create([
            'project_id' => $project->id,
            'title' => 'Objectives',
            'content' => 'A complete answer.',
            'sort_order' => 1,
        ]);
        ProjectApplicationSection::create([
            'project_id' => $project->id,
            'title' => 'Impact',
            'content' => '',
            'sort_order' => 2,
        ]);
        Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Ana',
            'last_name' => 'Popescu',
            'birth_date' => today()->subYears(20),
        ]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        $component = Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Recommended next step')
            ->assertSee('Continue writing the application')
            ->assertSee('1/2 sections')
            ->assertSee('50% of sections contain text')
            ->assertSee('1 incomplete')
            ->assertSee('Grant estimate')
            ->assertSee('KA152-YOU - Youth Exchanges')
            ->assertSee('Project details')
            ->assertSee('Project stage')
            ->assertSee('Project readiness check')
            ->assertSee('Critical items need attention')
            ->assertSee('Application answers');

        $this->assertStringContainsString('/estimate', $component->instance()->getModuleUrls()['budget']);
        $this->assertArrayHasKey('groups', $component->instance()->getProjectReadiness());
    }

    public function test_manager_can_use_an_allowed_lifecycle_transition(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Mark as Submitted')
            ->call('requestTransitionTo', 'submitted')
            ->assertSet('showTransitionReadinessModal', true)
            ->assertSee('Readiness warning before status change')
            ->assertSee('Continue anyway')
            ->call('confirmPendingTransition')
            ->assertSee('Awaiting the funding decision');

        $this->assertSame('submitted', $project->fresh()->status);
    }

    public function test_readiness_transition_warning_can_be_cancelled(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->call('requestTransitionTo', 'submitted')
            ->assertSet('showTransitionReadinessModal', true)
            ->call('closeTransitionReadinessModal')
            ->assertSet('showTransitionReadinessModal', false);

        $this->assertSame('writing', $project->fresh()->status);
    }

    public function test_viewer_does_not_see_project_mutation_actions(): void
    {
        [$workspace, $project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Continue writing the application')
            ->assertDontSee('Mark as Submitted')
            ->assertDontSee('Create tasks');
    }

    public function test_manager_can_create_tasks_from_readiness_issues_without_duplicates(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        ProjectApplicationSection::create([
            'project_id' => $project->id,
            'title' => 'Objectives',
            'content' => '',
            'sort_order' => 1,
        ]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        $component = Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Create tasks')
            ->call('createTasksFromReadiness');

        $this->assertGreaterThan(0, $project->tasks()->where('status', 'open')->count());
        $this->assertTrue($project->tasks()->where('title', 'Resolve: Project dates')->exists());
        $taskCount = $project->tasks()->count();

        $component->call('createTasksFromReadiness');

        $this->assertSame($taskCount, $project->tasks()->count());
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Overview Workspace']);
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $workspace->users()->attach($owner, ['role' => 'owner']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'ka_action' => 'ka152',
            'total_budget' => 15000,
        ]);

        $project->members()->attach($user, [
            'role' => $role === 'viewer'
                ? Project::PROJECT_ROLE_VIEWER
                : Project::PROJECT_ROLE_EDITOR,
        ]);

        return [$workspace, $project, $user];
    }
}
