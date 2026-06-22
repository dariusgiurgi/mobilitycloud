<?php

namespace Tests\Feature;

use App\Filament\Pages\WorkspaceReports;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_aggregate_only_accessible_projects_and_export_csv(): void
    {
        $workspace = Workspace::create(['name' => 'Report Workspace']);
        $viewer = User::factory()->create();
        $workspace->users()->attach($viewer, ['role' => 'viewer']);
        $visible = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Visible Portfolio Project',
            'status' => 'active',
            'approved_budget' => 10000,
        ]);
        $visible->budgetLines()->firstOrFail()->expenses()->create([
            'description' => 'Travel tickets',
            'expense_date' => '2026-07-05',
            'amount' => 1200,
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'amount_eur' => 1200,
        ]);
        Project::create([
            'workspace_id' => $workspace->id,
            'access_mode' => 'restricted',
            'name' => 'Hidden Portfolio Project',
            'status' => 'active',
            'approved_budget' => 50000,
        ]);

        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(WorkspaceReports::class)
            ->set('startDate', '2026-07-01')
            ->set('endDate', '2026-07-31')
            ->assertSee('Visible Portfolio Project')
            ->assertSee('1,200.00')
            ->assertDontSee('Hidden Portfolio Project');

        $this->get(route('workspaces.report.csv', [
            'workspace' => $workspace,
            'start' => '2026-07-01',
            'end' => '2026-07-31',
        ]))->assertOk()->assertDownload();
    }
}
