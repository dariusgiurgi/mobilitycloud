<?php

namespace Tests\Feature;

use App\Filament\Pages\Tenancy\EditWorkspaceProfile;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Filament\Auth\Pages\Login;
use Filament\Auth\Pages\Register;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_workspace_logs_into_onboarding_instead_of_registration_form(): void
    {
        $user = User::factory()->create(['email' => 'new-user@example.test']);
        Filament::setCurrentPanel('admin');

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'new-user@example.test',
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('app.onboarding'));
    }

    public function test_new_registration_opens_onboarding_instead_of_registration_form(): void
    {
        Filament::setCurrentPanel('admin');

        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'New Client',
                'email' => 'new-client@example.test',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('app.onboarding'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new-client@example.test']);
    }

    public function test_app_root_redirects_user_without_workspace_to_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/app')
            ->assertRedirect(route('app.onboarding'));
    }

    public function test_onboarding_page_does_not_force_workspace_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.onboarding'))
            ->assertOk()
            ->assertSee('You do not have an organisation yet.')
            ->assertSee('wait until someone invites you directly to a project')
            ->assertSee('No active invitations for this email address yet.')
            ->assertDontSee('Create workspace')
            ->assertDontSee('>Support<', false);
    }

    public function test_onboarding_lists_pending_email_invitations_for_the_account_email(): void
    {
        $workspace = Workspace::create(['name' => 'Inviting Organisation']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Invited Project',
            'status' => 'writing',
        ]);
        $user = User::factory()->create(['email' => 'invited@example.test']);
        $otherUser = User::factory()->create(['email' => 'other@example.test']);
        WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'email' => 'invited@example.test',
            'role' => 'project_editor',
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addDays(3),
        ]);
        WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'email' => 'other@example.test',
            'role' => 'project_editor',
            'token' => str_repeat('b', 64),
            'expires_at' => now()->addDays(3),
        ]);

        $this->actingAs($user)
            ->get(route('app.onboarding'))
            ->assertOk()
            ->assertSee('Invitations by email')
            ->assertSee('Invited Project')
            ->assertSee('Inviting Organisation')
            ->assertSee('Accept invitation')
            ->assertDontSee('No active invitations for this email address yet.');

        $this->actingAs($otherUser)
            ->get(route('app.onboarding'))
            ->assertOk()
            ->assertSee('Invited Project');
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
