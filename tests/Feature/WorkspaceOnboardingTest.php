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
            ->assertRedirect(Dashboard::getUrl(panel: 'admin'));

        $this->assertNull($user->fresh()->current_workspace_id);
    }

    public function test_new_registration_opens_project_dashboard_without_forcing_a_workspace(): void
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
            ->assertRedirect(Dashboard::getUrl(panel: 'admin'));

        $this->assertAuthenticated();
        $user = User::query()->where('email', 'new-client@example.test')->firstOrFail();
        $this->assertNull($user->current_workspace_id);
    }

    public function test_app_root_opens_dashboard_without_workspace_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/app')
            ->assertOk();

        $this->assertNull($user->fresh()->current_workspace_id);
    }

    public function test_onboarding_route_is_only_a_compatibility_redirect(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.onboarding'))
            ->assertRedirect(Dashboard::getUrl(panel: 'admin'));

        $this->assertNull($user->fresh()->current_workspace_id);
    }

    public function test_organisation_setup_updates_account_name_without_creating_workspace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('app.organisations.store'), [
                'name' => 'Scoala de Jocuri',
            ]);

        $response->assertRedirect(Dashboard::getUrl(panel: 'admin'));
        $this->assertSame('Scoala de Jocuri', $user->fresh()->name);
        $this->assertNull($user->fresh()->current_workspace_id);
        $this->assertDatabaseMissing('workspaces', ['name' => 'Scoala de Jocuri']);
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
        $this->assertSame($user->id, $project->owner_id);
        $this->assertNull($project->workspace_id);
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
