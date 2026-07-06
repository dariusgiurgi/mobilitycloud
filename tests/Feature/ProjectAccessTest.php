<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use App\Services\AccountWorkspaceService;
use App\Services\ProjectInvitationNotificationService;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        $project->members()->attach($selected, ['role' => Project::PROJECT_ROLE_VIEWER]);

        $this->assertTrue($project->canBeAccessedBy($admin));
        $this->assertTrue($project->canBeAccessedBy($selected));
        $this->assertFalse($project->canBeAccessedBy($unselected));

        $this->actingAs($unselected);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($unselected));
        Livewire::test(ListProjects::class)->assertDontSee('Restricted Mobility');

        $this->actingAs($selected);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($selected));
        Livewire::test(ListProjects::class)->assertSee('Restricted Mobility');
    }

    public function test_workspace_owner_can_update_project_roles_from_overview(): void
    {
        $workspace = Workspace::create(['name' => 'Managed Access']);
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $workspace->users()->attach($owner, ['role' => 'owner']);
        $workspace->users()->attach($viewer, ['role' => 'viewer']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Shared Project',
            'status' => 'writing',
        ]);
        $project->members()->attach($viewer, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->actingAs($owner);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertActionVisible('manageAccess')
            ->callAction('manageAccess', data: [
                'collaborators' => [
                    ['user_id' => $viewer->id, 'role' => Project::PROJECT_ROLE_VIEWER],
                ],
                'invite_email' => '',
                'invite_role' => Project::PROJECT_ROLE_EDITOR,
            ]);

        $this->assertSame('restricted', $project->fresh()->access_mode);
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $viewer->id,
            'role' => Project::PROJECT_ROLE_VIEWER,
        ]);
        $this->assertFalse($project->fresh()->canBeCollaboratedOnBy($viewer));
    }

    public function test_project_only_collaborator_can_enter_tenant_and_only_see_assigned_projects(): void
    {
        $workspace = Workspace::create(['name' => 'Project Only Workspace']);
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $workspace->users()->attach($owner, ['role' => 'owner']);
        $assigned = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Assigned Project',
            'status' => 'writing',
        ]);
        Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Hidden Project',
            'status' => 'writing',
        ]);
        $assigned->members()->attach($collaborator, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->assertTrue($collaborator->canAccessTenant($workspace));
        $this->assertNull($workspace->roleFor($collaborator));
        $this->assertTrue($assigned->canBeAccessedBy($collaborator));
        $this->assertTrue($assigned->canBeCollaboratedOnBy($collaborator));

        $this->actingAs($collaborator);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($collaborator));

        Livewire::test(ListProjects::class)
            ->assertSee('Assigned Project')
            ->assertDontSee('Hidden Project')
            ->assertSee('New project');
    }

    public function test_project_collaborators_have_the_same_application_menu_modules_as_the_owner(): void
    {
        $workspace = Workspace::create(['name' => 'Shared Menu Workspace']);
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $workspace->users()->attach($owner, ['role' => 'owner']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Shared Menu Project',
            'status' => 'writing',
        ]);
        $project->members()->attach($collaborator, ['role' => Project::PROJECT_ROLE_VIEWER]);

        $modules = [
            PlanCatalog::MODULE_PROJECTS,
            PlanCatalog::MODULE_CONTENT_LIBRARY,
            PlanCatalog::MODULE_PUBLIC_LIBRARY,
            PlanCatalog::MODULE_TASKS,
            PlanCatalog::MODULE_REPORTS,
        ];

        $this->actingAs($owner);
        Filament::setTenant($workspace);
        $ownerMenu = collect($modules)
            ->mapWithKeys(fn (string $module): array => [$module => PlatformAccess::canUse($module)])
            ->all();

        $this->actingAs($collaborator);
        Filament::setTenant($workspace);
        $collaboratorMenu = collect($modules)
            ->mapWithKeys(fn (string $module): array => [$module => PlatformAccess::canUse($module)])
            ->all();

        $this->assertSame($ownerMenu, $collaboratorMenu);
        $this->assertTrue($collaboratorMenu[PlanCatalog::MODULE_PROJECTS]);
        $this->assertTrue($collaboratorMenu[PlanCatalog::MODULE_REPORTS]);
    }

    public function test_project_access_action_can_invite_a_project_only_collaborator(): void
    {
        Notification::fake();
        $workspace = Workspace::create(['name' => 'Invite Project Workspace']);
        $owner = User::factory()->create();
        $workspace->users()->attach($owner, ['role' => 'owner']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Invitation Project',
            'status' => 'writing',
        ]);

        $this->actingAs($owner);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->callAction('manageAccess', data: [
                'collaborators' => [],
                'invite_email' => 'project-only@example.test',
                'invite_role' => Project::PROJECT_ROLE_EDITOR,
            ]);

        $this->assertDatabaseHas('workspace_invitations', [
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'email' => 'project-only@example.test',
            'role' => 'project_editor',
        ]);
        Notification::assertSentOnDemand(WorkspaceInvitationNotification::class);
    }

    public function test_inviting_existing_user_keeps_access_pending_until_acceptance(): void
    {
        Notification::fake();
        $workspace = Workspace::create(['name' => 'Invite Existing Workspace']);
        $owner = User::factory()->create();
        $existing = User::factory()->create(['email' => 'existing@example.test']);
        $workspace->users()->attach($owner, ['role' => 'owner']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Existing User Project',
            'status' => 'writing',
        ]);

        $this->actingAs($owner);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->callAction('manageAccess', data: [
                'collaborators' => [],
                'invite_email' => 'existing@example.test',
                'invite_role' => Project::PROJECT_ROLE_EDITOR,
            ]);

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $existing->id,
        ]);
        $this->assertDatabaseHas('workspace_invitations', [
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'email' => 'existing@example.test',
            'role' => 'project_editor',
            'accepted_at' => null,
        ]);
        Notification::assertSentOnDemand(WorkspaceInvitationNotification::class);
        Notification::assertSentTo($existing, \Filament\Notifications\DatabaseNotification::class);
    }

    public function test_project_invitation_creates_an_in_app_notification_for_existing_account(): void
    {
        $workspace = Workspace::create(['name' => 'In App Invite Workspace']);
        $owner = User::factory()->create(['name' => 'Darius Owner']);
        $existing = User::factory()->create(['email' => 'existing@example.test']);
        $workspace->users()->attach($owner, ['role' => 'owner']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Visible Invitation Project',
            'status' => 'writing',
        ]);
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'email' => 'existing@example.test',
            'role' => 'project_viewer',
            'token' => str_repeat('e', 64),
            'expires_at' => now()->addDays(3),
            'invited_by' => $owner->id,
        ]);

        $this->assertTrue(app(ProjectInvitationNotificationService::class)->notifyExistingAccount($invitation));

        $notification = $existing->notifications()->sole();
        $this->assertSame('Project invitation received', $notification->data['title']);
        $this->assertStringContainsString('Darius Owner invited you to Visible Invitation Project as Viewer', $notification->data['body']);
        $this->assertSame($invitation->id, data_get($notification->data, 'viewData.invitation_id'));
        $this->assertNotEmpty($notification->data['actions']);

        $this->assertFalse(app(ProjectInvitationNotificationService::class)->notifyExistingAccount($invitation));
        $this->assertSame(1, $existing->notifications()->count());
    }

    public function test_project_invitation_grants_access_only_after_acceptance(): void
    {
        $workspace = Workspace::create(['name' => 'Accept Invite Workspace']);
        $existing = User::factory()->create(['email' => 'existing@example.test']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Accepted Project',
            'status' => 'writing',
        ]);
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'email' => 'existing@example.test',
            'role' => 'project_manager',
            'token' => str_repeat('c', 64),
            'expires_at' => now()->addDays(3),
        ]);

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $existing->id,
        ]);

        $accountWorkspace = app(AccountWorkspaceService::class)->ensureFor($existing);

        $this->actingAs($existing)
            ->get(route('workspace-invitations.accept', $invitation->token))
            ->assertRedirect(ProjectResource::getUrl('overview', ['record' => $project], panel: 'admin', tenant: $accountWorkspace));

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $existing->id,
            'role' => Project::PROJECT_ROLE_EDITOR,
        ]);
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertNotSame($workspace->id, $existing->fresh()->current_workspace_id);
        $this->assertSame('owner', $existing->fresh()->currentWorkspace?->roleFor($existing->fresh()));
    }

    public function test_project_invitation_cannot_be_accepted_after_project_is_removed(): void
    {
        $workspace = Workspace::create(['name' => 'Removed Invite Workspace']);
        $existing = User::factory()->create(['email' => 'existing@example.test']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Removed Project',
            'status' => 'writing',
        ]);
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'email' => 'existing@example.test',
            'role' => 'project_editor',
            'token' => str_repeat('d', 64),
            'expires_at' => now()->addDays(3),
        ]);
        $project->delete();

        $this->actingAs($existing)
            ->get(route('workspace-invitations.accept', $invitation->token))
            ->assertGone();

        $this->assertNull($invitation->fresh()->accepted_at);
        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $existing->id,
        ]);
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
        $accountWorkspace = app(AccountWorkspaceService::class)->ensureFor($viewer);

        $restrictedAccountUrl = ProjectResource::getUrl('overview', ['record' => $restricted], tenant: $accountWorkspace);
        $otherAccountUrl = ProjectResource::getUrl('overview', ['record' => $otherProject], tenant: $accountWorkspace);

        $this->get(ProjectResource::getUrl('overview', ['record' => $restricted], tenant: $workspace))
            ->assertRedirect($restrictedAccountUrl);
        $this->get(ProjectResource::getUrl('overview', ['record' => $otherProject], tenant: $workspace))
            ->assertRedirect($otherAccountUrl);

        Filament::setTenant($accountWorkspace);

        $this->get($restrictedAccountUrl)->assertNotFound();
        $this->get($otherAccountUrl)->assertNotFound();
    }
}
