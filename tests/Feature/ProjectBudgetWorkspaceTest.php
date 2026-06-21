<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectBoard;
use App\Filament\Resources\Projects\Pages\ViewProjectEstimate;
use App\Models\BudgetTransfer;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectBudgetWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimator_explains_the_planning_stage_and_persists_changes(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member', 'writing');
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectEstimate::class, ['record' => $project->id])
            ->assertSee('Grant estimator')
            ->assertSee('Writing stage')
            ->assertSee('Changes are saved automatically')
            ->set('persons', 12)
            ->assertSet('persons', 12);

        $this->assertSame(12, $project->fresh()->action_data['estimate']['inputs']['persons']);
    }

    public function test_budget_board_surfaces_allocation_and_document_readiness(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member', 'active');
        $project->update(['approved_budget' => 1000]);
        $travel = $project->budgetLines()->orderBy('sort_order')->first();
        $travel->update(['allocated_budget' => 800]);
        $travel->expenses()->create([
            'description' => 'Travel tickets',
            'expense_date' => today(),
            'amount' => 150,
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'amount_eur' => 150,
            'position' => 0,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectBoard::class, ['record' => $project->id])
            ->assertSee('Budget control')
            ->assertSee('1 expenses · 0 with supporting files')
            ->assertSee('€ 1,000.00')
            ->assertSee('€ 150.00')
            ->assertSee('€ 200.00')
            ->assertSee('Basket allocations are below the effective grant by € 200.00.')
            ->assertSee('Transfer budget')
            ->assertSee('Add expense');
    }

    public function test_viewer_sees_budget_pages_without_mutation_controls(): void
    {
        [$workspace, $project, $viewer] = $this->workspaceProjectAndUser('viewer', 'active');
        [$from, $to] = $project->budgetLines()->orderBy('sort_order')->limit(2)->get();
        BudgetTransfer::create([
            'project_id' => $project->id,
            'from_budget_line_id' => $from->id,
            'to_budget_line_id' => $to->id,
            'amount' => 10,
            'status' => 'active',
            'created_by' => $viewer->id,
        ]);
        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectBoard::class, ['record' => $project->id])
            ->assertSee('Read-only access')
            ->assertSee('Active')
            ->assertDontSee('Reversed')
            ->assertDontSee('Transfer budget')
            ->assertDontSee('Add expense')
            ->assertDontSee('Add new basket');

        $project->update(['status' => 'writing']);

        Livewire::test(ViewProjectEstimate::class, ['record' => $project->id])
            ->assertSee('Read-only access')
            ->assertSee('Grant estimator');
    }

    private function workspaceProjectAndUser(string $role, string $status): array
    {
        $workspace = Workspace::create(['name' => 'Budget Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Budget Project',
            'status' => $status,
            'ka_action' => 'ka152',
            'total_budget' => 1000,
        ]);

        return [$workspace, $project, $user];
    }
}
