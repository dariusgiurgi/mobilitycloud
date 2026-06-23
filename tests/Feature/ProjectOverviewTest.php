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
            ->assertSee('Project stage');

        $this->assertStringContainsString('/estimate', $component->instance()->getModuleUrls()['budget']);
    }

    public function test_manager_can_use_an_allowed_lifecycle_transition(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Mark as Submitted')
            ->call('transitionTo', 'submitted')
            ->assertSee('Awaiting the funding decision');

        $this->assertSame('submitted', $project->fresh()->status);
    }

    public function test_viewer_does_not_see_project_mutation_actions(): void
    {
        [$workspace, $project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Continue writing the application')
            ->assertDontSee('Mark as Submitted');
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Overview Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'ka_action' => 'ka152',
            'total_budget' => 15000,
        ]);

        return [$workspace, $project, $user];
    }
}
