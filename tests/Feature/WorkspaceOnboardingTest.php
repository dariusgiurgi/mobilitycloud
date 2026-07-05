<?php

namespace Tests\Feature;

use App\Filament\Pages\Tenancy\EditWorkspaceProfile;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Filament\Auth\Pages\Login;
use Filament\Auth\Pages\Register;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_workspace_logs_directly_into_project_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'new-user@example.test']);
        Filament::setCurrentPanel('admin');

        $component = Livewire::test(Login::class)
            ->fillForm([
                'email' => 'new-user@example.test',
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $workspace = $user->fresh()->currentWorkspace;

        $this->assertNotNull($workspace);
        $component->assertRedirect(Dashboard::getUrl(panel: 'admin', tenant: $workspace));
        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_new_registration_creates_internal_project_container(): void
    {
        Filament::setCurrentPanel('admin');

        $component = Livewire::test(Register::class)
            ->fillForm([
                'name' => 'New Client',
                'email' => 'new-client@example.test',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertAuthenticated();
        $user = User::query()->where('email', 'new-client@example.test')->firstOrFail();
        $workspace = $user->currentWorkspace;

        $this->assertNotNull($workspace);
        $this->assertSame('New Client projects', $workspace->name);
        $component->assertRedirect(Dashboard::getUrl(panel: 'admin', tenant: $workspace));
    }

    public function test_app_root_creates_internal_container_instead_of_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/app')
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->currentWorkspace);
    }

    public function test_onboarding_route_is_only_a_compatibility_redirect(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.onboarding'))
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->currentWorkspace);
    }

    public function test_user_without_workspace_can_create_organisation_from_onboarding(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('app.organisations.store'), [
                'name' => 'Scoala de Jocuri',
            ]);

        $workspace = Workspace::query()->where('name', 'Scoala de Jocuri')->firstOrFail();

        $response->assertRedirect(Dashboard::getUrl(panel: 'admin', tenant: $workspace));
        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        $this->assertSame($workspace->id, $user->fresh()->current_workspace_id);
    }

    public function test_authenticated_user_no_longer_opens_workspace_registration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/app/new')
            ->assertNotFound();
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
