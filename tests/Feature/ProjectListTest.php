<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ListProjects;
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
        $workspace->users()->attach($user, ['role' => 'member']);
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
        Filament::setTenant($workspace);

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
        Filament::setTenant($workspace);
        Livewire::test(ListProjects::class)->assertSee('New project');

        $this->actingAs($member);
        Filament::setTenant($workspace);
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
        Filament::setTenant($invitingWorkspace);

        Livewire::test(ListProjects::class)
            ->assertSee('Shared External Project')
            ->assertSee('New project');

        $userAccount = app(AccountWorkspaceService::class)->ensureFor($user);
        Project::create([
            'workspace_id' => $userAccount->id,
            'name' => 'My Free Plan Project',
            'status' => 'writing',
        ]);

        Livewire::test(ListProjects::class)->assertDontSee('New project');
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
        Filament::setTenant($first);

        Livewire::test(ListProjects::class)
            ->assertSee('Owned Project')
            ->assertSee('Shared Project')
            ->assertSee('Your project')
            ->assertSee('Owner: Second Internal Container')
            ->assertSee('Editor');
    }
}
