<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DashboardWorkspace;
use App\Filament\Widgets\ProjectStatsOverview;
use App\Models\Participant;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_surfaces_current_work_and_real_attention_items(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser();
        $project->update([
            'status' => 'active',
            'approved_budget' => 12000,
            'start_date' => today()->subMonth(),
            'end_date' => today()->addMonths(5),
            'mobility_start_date' => today()->addDays(5),
        ]);

        Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Ana',
            'last_name' => 'Popescu',
            'birth_date' => today()->subYears(20),
        ]);

        $project->budgetLines()->first()->expenses()->create([
            'description' => 'Local transport',
            'amount' => 300,
            'amount_eur' => 300,
            'currency' => 'EUR',
            'exchange_rate' => 1,
        ]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(DashboardWorkspace::class)
            ->assertSee('Needs attention')
            ->assertSee('How priorities are detected')
            ->assertSee('Readiness')
            ->assertSee('Critical items need attention')
            ->assertSee('Mobility starts in 5 days')
            ->assertSee('1 participant record incomplete')
            ->assertSee('1 expense without evidence')
            ->assertSee('Current projects')
            ->assertSee('Youth Exchange')
            ->assertSee('Upcoming milestones')
            ->assertSee('Quick actions')
            ->assertSee('Manage expenses')
            ->assertSee('Add participants')
            ->assertSee('Create documents');
    }

    public function test_dashboard_uses_the_professional_widget_order(): void
    {
        [$workspace, , $user] = $this->workspaceProjectAndUser();
        $this->actingAs($user);
        Filament::setTenant($workspace);

        $dashboard = app(Dashboard::class);

        $this->assertSame([
            ProjectStatsOverview::class,
            DashboardWorkspace::class,
        ], $dashboard->getWidgets());
        $this->assertSame(
            'Your projects, priorities and upcoming milestones.',
            $dashboard->getSubheading(),
        );
    }

    public function test_dashboard_lists_pending_project_invitations_for_account_email(): void
    {
        [$workspace, , $user] = $this->workspaceProjectAndUser();
        $invitingWorkspace = Workspace::create(['name' => 'Inviting Portfolio']);
        $invitedProject = Project::create([
            'workspace_id' => $invitingWorkspace->id,
            'name' => 'Shared Project',
            'status' => 'writing',
        ]);
        WorkspaceInvitation::create([
            'workspace_id' => $invitingWorkspace->id,
            'project_id' => $invitedProject->id,
            'email' => $user->email,
            'role' => 'project_editor',
            'token' => str_repeat('d', 64),
            'expires_at' => now()->addDays(3),
        ]);

        $this->actingAs($user);
        Filament::setTenant($workspace);
        $this->assertSame(0, $user->notifications()->count());

        Livewire::test(DashboardWorkspace::class)
            ->assertSee('Pending invitations')
            ->assertSee('Shared Project')
            ->assertSee('Inviting Portfolio')
            ->assertSee('Accept');

        $notification = $user->notifications()->sole();
        $this->assertSame('Project invitation received', $notification->data['title']);
        $this->assertSame($invitedProject->id, data_get($notification->data, 'viewData.project_id'));
    }

    public function test_project_stats_render_for_project_only_collaborators(): void
    {
        $workspace = Workspace::create(['name' => 'External Stats Workspace']);
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $workspace->users()->attach($owner, ['role' => 'owner']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'External Active Project',
            'status' => 'active',
            'approved_budget' => 5000,
        ]);
        $project->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ProjectStatsOverview::class)
            ->assertSee('Active projects')
            ->assertSee('Approved funding')
            ->assertSee('5,000.00');
    }

    public function test_workspace_member_can_render_the_complete_dashboard_page(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser();
        $project->update([
            'status' => 'active',
            'approved_budget' => 12000,
        ]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        $this->get(Dashboard::getUrl(tenant: $workspace))
            ->assertOk()
            ->assertSee('Project dashboard')
            ->assertSee('Approved funding')
            ->assertSee('Needs attention')
            ->assertSee('Current projects')
            ->assertSee('Quick actions')
            ->assertDontSee('wire:poll.5s', false)
            ->assertDontSee('wire:poll.30s', false);
    }

    private function workspaceProjectAndUser(): array
    {
        $workspace = Workspace::create(['name' => 'Dashboard Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'owner']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'total_budget' => 15000,
        ]);

        return [$workspace, $project, $user];
    }
}
