<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DashboardOverview;
use App\Filament\Widgets\ProjectStatsOverview;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_surfaces_current_work_and_real_attention_items(): void
    {
        [$project, $user] = $this->projectAndOwner();
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

        Livewire::test(DashboardOverview::class)
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
        [, $user] = $this->projectAndOwner();
        $this->actingAs($user);

        $dashboard = app(Dashboard::class);

        $this->assertSame([
            ProjectStatsOverview::class,
            DashboardOverview::class,
        ], $dashboard->getWidgets());
        $this->assertSame(
            'Your projects, priorities and upcoming milestones.',
            $dashboard->getSubheading(),
        );
    }

    public function test_dashboard_lists_pending_project_invitations_for_account_email(): void
    {
        [, $user] = $this->projectAndOwner();
        $inviter = User::factory()->create(['name' => 'Inviting Portfolio']);
        $invitedProject = Project::create([
            'owner_id' => $inviter->id,
            'name' => 'Shared Project',
            'status' => 'writing',
        ]);
        ProjectInvitation::create([
            'project_id' => $invitedProject->id,
            'invited_by' => $inviter->id,
            'email' => $user->email,
            'role' => 'project_editor',
            'token' => str_repeat('d', 64),
            'expires_at' => now()->addDays(3),
        ]);

        $this->actingAs($user);
        $this->assertSame(0, $user->notifications()->count());

        Livewire::test(DashboardOverview::class)
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
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'External Active Project',
            'status' => 'active',
            'approved_budget' => 5000,
        ]);
        $project->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->actingAs($user);

        Livewire::test(ProjectStatsOverview::class)
            ->assertSee('Active projects')
            ->assertSee('Approved funding')
            ->assertSee('5,000.00');
    }

    public function test_account_member_can_render_the_complete_dashboard_page(): void
    {
        [$project, $user] = $this->projectAndOwner();
        $project->update([
            'status' => 'active',
            'approved_budget' => 12000,
        ]);

        $this->actingAs($user);

        $this->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee('Project dashboard')
            ->assertSee('Approved funding')
            ->assertSee('Needs attention')
            ->assertSee('Current projects')
            ->assertSee('Quick actions')
            ->assertDontSee('wire:poll.5s', false)
            ->assertDontSee('wire:poll.30s', false);
    }

    private function projectAndOwner(): array
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'total_budget' => 15000,
        ]);

        return [$project, $user];
    }
}
