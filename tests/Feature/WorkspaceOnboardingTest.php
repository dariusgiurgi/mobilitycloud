<?php

namespace Tests\Feature;

use App\Filament\Pages\Tenancy\EditWorkspaceProfile;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_without_workspace_can_open_workspace_registration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/app/new')
            ->assertOk()
            ->assertSee('Set up your organisation')
            ->assertDontSee('Create workspace');
    }

    public function test_workspace_profile_groups_identity_and_legal_details(): void
    {
        [$workspace, $user] = $this->workspaceAndOwner();
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(EditWorkspaceProfile::class)
            ->assertSee('Workspace identity')
            ->assertSee('Legal and billing details')
            ->fillForm(['billing_country' => 'ro'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('RO', $workspace->fresh()->billing_country);
    }

    public function test_project_creation_explains_the_flow_and_opens_overview(): void
    {
        [$workspace, $user] = $this->workspaceAndOwner();
        $this->actingAs($user);
        Filament::setTenant($workspace);

        $component = Livewire::test(CreateProject::class)
            ->assertSee('Create a new project')
            ->assertSee('Application, budget, participants and documents')
            ->fillForm([
                'name' => 'New Mobility Project',
                'total_budget' => 12000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('name', 'New Mobility Project')->firstOrFail();

        $component->assertRedirect(ProjectResource::getUrl('overview', ['record' => $project]));
        $this->assertSame($workspace->id, $project->workspace_id);
        $this->assertNull($project->ka_action);
    }

    private function workspaceAndOwner(): array
    {
        $workspace = Workspace::create(['name' => 'Onboarding Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'owner']);

        return [$workspace, $user];
    }
}
