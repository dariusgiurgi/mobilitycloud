<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectBoard;
use App\Filament\Resources\Projects\Pages\ViewProjectEstimate;
use App\Models\BudgetTransfer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectBudgetWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimator_explains_the_planning_stage_and_persists_changes(): void
    {
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_EDITOR, 'writing');
        $this->actingAs($user);

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
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_EDITOR, 'active');
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

    public function test_budget_board_adds_baskets_and_recalculates_expenses_with_project_rates(): void
    {
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_EDITOR, 'active');
        $project->update(['currencies' => [['code' => 'RON', 'rate' => 5]]]);
        $line = $project->budgetLines()->orderBy('sort_order')->first();

        $this->actingAs($user);

        Livewire::test(ViewProjectBoard::class, ['record' => $project->id])
            ->call('openBasketCreate')
            ->assertSet('showBasketModal', true)
            ->set('basketTitle', 'QA Bot Materials')
            ->set('basketEmoji', '🎨')
            ->call('saveBasket')
            ->assertSet('showBasketModal', false)
            ->call('addExpense', $line->id);

        $expense = $line->expenses()->latest('id')->firstOrFail();

        Livewire::test(ViewProjectBoard::class, ['record' => $project->id])
            ->call('updateExpense', $expense->id, 'description', 'QA Bot RON materials')
            ->call('updateExpense', $expense->id, 'currency', 'RON')
            ->call('updateExpense', $expense->id, 'amount', 500);

        $this->assertDatabaseHas('budget_lines', [
            'project_id' => $project->id,
            'title' => 'QA Bot Materials',
            'emoji' => '🎨',
        ]);
        $this->assertSame('QA Bot RON materials', $expense->fresh()->description);
        $this->assertSame('RON', $expense->fresh()->currency);
        $this->assertSame('5.000000', $expense->fresh()->exchange_rate);
        $this->assertSame('100.00', $expense->fresh()->amount_eur);
    }

    public function test_viewer_sees_budget_pages_without_mutation_controls(): void
    {
        [$project, $viewer] = $this->projectAndUser(Project::PROJECT_ROLE_VIEWER, 'active');
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

    private function projectAndUser(string $role, string $status): array
    {
        $user = User::factory()->create();
        $owner = $role === Project::PROJECT_ROLE_VIEWER
            ? User::factory()->create()
            : $user;

        $project = Project::create([
            'owner_id' => $owner->id,
            'workspace_id' => null,
            'access_mode' => 'restricted',
            'name' => 'Budget Project',
            'status' => $status,
            'ka_action' => 'ka152',
            'total_budget' => 1000,
        ]);

        $project->budgetLines()->createMany([
            [
                'title' => 'Travel',
                'emoji' => '✈️',
                'allocated_budget' => 0,
                'sort_order' => 0,
            ],
            [
                'title' => 'Organisational Support',
                'emoji' => '🙋',
                'allocated_budget' => 0,
                'sort_order' => 1,
            ],
        ]);

        if ($role === Project::PROJECT_ROLE_VIEWER) {
            $project->members()->attach($user, ['role' => $role]);
        }

        return [$project, $user];
    }
}
