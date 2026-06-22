<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_restricted_projects_are_visible_only_to_selected_collaborators_and_workspace_admins(): void
    {
        $workspace = Workspace::create(['name' => 'Access Workspace']);
        $admin = User::factory()->create();
        $selected = User::factory()->create();
        $unselected = User::factory()->create();
        $workspace->users()->attach($admin, ['role' => 'admin']);
        $workspace->users()->attach($selected, ['role' => 'viewer']);
        $workspace->users()->attach($unselected, ['role' => 'viewer']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'access_mode' => 'restricted',
            'name' => 'Restricted Mobility',
            'status' => 'writing',
        ]);
        $project->members()->attach($selected);

        $this->assertTrue($project->canBeAccessedBy($admin));
        $this->assertTrue($project->canBeAccessedBy($selected));
        $this->assertFalse($project->canBeAccessedBy($unselected));

        $this->actingAs($unselected);
        Filament::setTenant($workspace);
        Livewire::test(ListProjects::class)->assertDontSee('Restricted Mobility');

        $this->actingAs($selected);
        Filament::setTenant($workspace);
        Livewire::test(ListProjects::class)->assertSee('Restricted Mobility');
    }

    public function test_admin_can_update_project_access_from_overview(): void
    {
        $workspace = Workspace::create(['name' => 'Managed Access']);
        $admin = User::factory()->create();
        $viewer = User::factory()->create();
        $workspace->users()->attach($admin, ['role' => 'admin']);
        $workspace->users()->attach($viewer, ['role' => 'viewer']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Shared Project',
            'status' => 'writing',
        ]);

        $this->actingAs($admin);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertActionVisible('manageAccess')
            ->callAction('manageAccess', data: [
                'access_mode' => 'restricted',
                'member_ids' => [$viewer->id],
            ]);

        $this->assertSame('restricted', $project->fresh()->access_mode);
        $this->assertTrue($project->members()->whereKey($viewer->id)->exists());
    }

    public function test_project_routes_cannot_cross_workspace_or_restricted_access_boundaries(): void
    {
        $workspace = Workspace::create(['name' => 'Current Workspace']);
        $viewer = User::factory()->create();
        $workspace->users()->attach($viewer, ['role' => 'viewer']);
        $restricted = Project::create([
            'workspace_id' => $workspace->id,
            'access_mode' => 'restricted',
            'name' => 'Hidden Project',
            'status' => 'writing',
        ]);
        $otherWorkspace = Workspace::create(['name' => 'Other Workspace']);
        $otherProject = Project::create([
            'workspace_id' => $otherWorkspace->id,
            'name' => 'Other Project',
            'status' => 'writing',
        ]);

        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        $this->get(ProjectResource::getUrl('overview', ['record' => $restricted], tenant: $workspace))->assertNotFound();
        $this->get(ProjectResource::getUrl('overview', ['record' => $otherProject], tenant: $workspace))->assertNotFound();
    }
}
