<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AccountWorkspaceService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectListTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_cards_use_current_statuses_and_effective_budget(): void
    {
        $workspace = Workspace::create(['name' => 'Projects Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'owner']);
        Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Active Mobility',
            'acronym' => 'AM',
            'status' => 'active',
            'total_budget' => 15000,
            'approved_budget' => 12000,
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-01',
        ]);

        $this->actingAs($user);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($user));

        Livewire::test(ListProjects::class)
            ->assertSee('Active Mobility')
            ->assertSee('Project order and progress')
            ->assertSee('Active')
            ->assertSee('Approved funding')
            ->assertSee('12,000.00')
            ->assertDontSee('15,000.00')
            ->assertSee('Open project');
    }

    public function test_project_creation_uses_the_authenticated_users_own_plan(): void
    {
        $workspace = Workspace::create(['name' => 'Creation Workspace']);
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace->users()->attach($owner, ['role' => 'owner']);
        $workspace->users()->attach($member, ['role' => 'member']);

        $this->actingAs($owner);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($owner));
        Livewire::test(ListProjects::class)->assertSee('New project');

        $this->actingAs($member);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($member));
        Livewire::test(ListProjects::class)->assertSee('New project');

        $memberAccount = app(AccountWorkspaceService::class)->ensureFor($member);
        Project::create([
            'workspace_id' => $memberAccount->id,
            'name' => 'Member Own Free Project',
            'status' => 'writing',
        ]);

        Livewire::test(ListProjects::class)->assertDontSee('New project');
    }

    public function test_invited_projects_do_not_consume_the_free_project_limit(): void
    {
        $invitingWorkspace = Workspace::create(['name' => 'External Owner']);
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $invitingWorkspace->users()->attach($owner, ['role' => 'owner']);

        $shared = Project::create([
            'workspace_id' => $invitingWorkspace->id,
            'name' => 'Shared External Project',
            'status' => 'writing',
        ]);
        $shared->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->actingAs($user);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($user));

        Livewire::test(ListProjects::class)
            ->assertSee('Shared External Project')
            ->assertSee('New project');

        $userAccount = app(AccountWorkspaceService::class)->ensureFor($user);
        $this->assertStringContainsString(
            $userAccount->slug.'/projects/create',
            Livewire::test(ListProjects::class)->html(),
        );
        Project::create([
            'workspace_id' => $userAccount->id,
            'name' => 'My Free Plan Project',
            'status' => 'writing',
        ]);

        Livewire::test(ListProjects::class)->assertDontSee('New project');
    }

    public function test_create_project_route_under_an_invited_workspace_redirects_to_the_users_account_workspace(): void
    {
        $invitingWorkspace = Workspace::create(['name' => 'Invited Tenant']);
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $invitingWorkspace->users()->attach($owner, ['role' => 'owner']);
        $shared = Project::create([
            'workspace_id' => $invitingWorkspace->id,
            'name' => 'Shared Tenant Project',
            'status' => 'writing',
        ]);
        $shared->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);
        $userAccount = app(AccountWorkspaceService::class)->ensureFor($user);

        $this->actingAs($user);

        $this->get(ProjectResource::getUrl('create', tenant: $invitingWorkspace))
            ->assertRedirect(ProjectResource::getUrl('create', tenant: $userAccount));
    }

    public function test_old_project_links_under_the_owner_workspace_redirect_to_the_users_account_workspace(): void
    {
        $ownerWorkspace = Workspace::create(['name' => 'Owner Workspace']);
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $ownerWorkspace->users()->attach($owner, ['role' => 'owner']);
        $project = Project::create([
            'workspace_id' => $ownerWorkspace->id,
            'name' => 'Externally Owned Project',
            'status' => 'writing',
        ]);
        $project->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);
        $userAccount = app(AccountWorkspaceService::class)->ensureFor($user);

        $this->actingAs($user);

        $this->get(ProjectResource::getUrl('overview', ['record' => $project], tenant: $ownerWorkspace))
            ->assertRedirect(ProjectResource::getUrl('overview', ['record' => $project], tenant: $userAccount));
    }

    public function test_project_created_after_external_collaboration_is_stored_under_the_users_account_workspace(): void
    {
        $externalWorkspace = Workspace::create(['name' => 'External Project Owner']);
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $externalWorkspace->users()->attach($owner, ['role' => 'owner']);
        $shared = Project::create([
            'workspace_id' => $externalWorkspace->id,
            'name' => 'External Collaboration',
            'status' => 'writing',
        ]);
        $shared->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);
        $userAccount = app(AccountWorkspaceService::class)->ensureFor($user);

        $this->actingAs($user);
        Filament::setTenant($userAccount);

        $component = Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'My Account Project',
                'total_budget' => 1000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('name', 'My Account Project')->firstOrFail();
        $this->assertSame($userAccount->id, $project->workspace_id);
        $this->assertTrue($project->canBeAccessedBy($user));
        $component->assertRedirect(ProjectResource::getUrl('overview', ['record' => $project], tenant: $userAccount));
    }

    public function test_project_list_shows_accessible_projects_across_internal_containers(): void
    {
        $first = Workspace::create(['name' => 'First Internal Container']);
        $second = Workspace::create(['name' => 'Second Internal Container']);
        $user = User::factory()->create();
        $first->users()->attach($user, ['role' => 'owner']);

        Project::create([
            'workspace_id' => $first->id,
            'name' => 'Owned Project',
            'status' => 'writing',
        ]);

        $shared = Project::create([
            'workspace_id' => $second->id,
            'name' => 'Shared Project',
            'status' => 'active',
        ]);
        $shared->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->actingAs($user);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($user));

        Livewire::test(ListProjects::class)
            ->assertSee('Owned Project')
            ->assertSee('Shared Project')
            ->assertSee('Your project')
            ->assertSee('Owner: Second Internal Container')
            ->assertSee('Editor');
    }
}
