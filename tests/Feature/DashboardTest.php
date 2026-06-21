<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DashboardWorkspace;
use App\Filament\Widgets\ProjectStatsOverview;
use App\Models\Participant;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
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
            'Dashboard Workspace · Your projects, priorities and upcoming milestones.',
            $dashboard->getSubheading(),
        );
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
            ->assertSee('Workspace overview')
            ->assertSee('Approved funding')
            ->assertSee('Needs attention')
            ->assertSee('Current projects')
            ->assertSee('Quick actions');
    }

    private function workspaceProjectAndUser(): array
    {
        $workspace = Workspace::create(['name' => 'Dashboard Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'total_budget' => 15000,
        ]);

        return [$workspace, $project, $user];
    }
}
