<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ViewProjectBoard;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_keep_approval_as_clear_read_only_information(): void
    {
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_EDITOR);
        $this->actingAs($user);

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->assertSee('Project details')
            ->assertSee('Involved organisations')
            ->assertSee('Approval and invoice')
            ->assertSee('Approval not recorded yet')
            ->assertSee('Operational finance settings')
            ->assertSee('Project currencies')
            ->assertSee('Other settings')
            ->assertSee('More actions')
            ->assertDontSee('Application template')
            ->assertDontSee('Timeline and mobilities')
            ->assertDontSee('Funding and taxation')
            ->assertDontSee('Total budget (€)')
            ->assertDontSee('Approved budget (€)');
    }

    public function test_project_settings_validate_financial_percentages_without_mobility_fields(): void
    {
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_EDITOR);
        $this->actingAs($user);

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->fillForm([
                'first_tranche_pct' => 110,
                'withholding_tax_rate' => 101,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'first_tranche_pct' => 'max',
                'withholding_tax_rate' => 'max',
            ]);
    }

    public function test_expense_prefix_is_saved_in_a_consistent_format(): void
    {
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_EDITOR);
        $this->actingAs($user);

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->fillForm(['expense_prefix' => 'eras-26'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('ERAS-26', $project->fresh()->expense_prefix);
    }

    public function test_project_settings_manage_project_currencies_and_recalculate_project_expenses(): void
    {
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_EDITOR);
        $otherOwner = User::factory()->create();
        $otherProject = Project::create([
            'owner_id' => $otherOwner->id,
            'access_mode' => 'restricted',
            'name' => 'Other project',
            'status' => 'active',
            'currencies' => [['code' => 'RON', 'rate' => 5.07]],
        ]);
        $otherLine = $otherProject->budgetLines()->create([
            'title' => 'Other travel',
            'emoji' => '✈️',
            'allocated_budget' => 1000,
            'sort_order' => 0,
        ]);
        $project->update(['currencies' => [['code' => 'RON', 'rate' => 5.07]]]);
        $expense = $project->budgetLines()->first()->expenses()->create([
            'description' => 'Venue',
            'amount' => 507,
            'currency' => 'RON',
            'exchange_rate' => 5.07,
            'amount_eur' => 100,
        ]);
        $otherExpense = $otherLine->expenses()->create([
            'description' => 'Other venue',
            'amount' => 507,
            'currency' => 'RON',
            'exchange_rate' => 5.07,
            'amount_eur' => 100,
        ]);

        $this->actingAs($user);

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->fillForm([
                'currencies' => [
                    ['code' => 'ron', 'rate' => 5],
                    ['code' => 'eur', 'rate' => 1],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([['code' => 'RON', 'rate' => 5]], $project->fresh()->currencies);
        $this->assertSame('5.000000', $expense->fresh()->exchange_rate);
        $this->assertSame('101.40', $expense->fresh()->amount_eur);
        $this->assertSame('5.070000', $otherExpense->fresh()->exchange_rate);
        $this->assertSame('100.00', $otherExpense->fresh()->amount_eur);
    }

    public function test_budget_uses_project_currencies_only(): void
    {
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_EDITOR);
        $project->update([
            'status' => 'active',
            'currencies' => [['code' => 'USD', 'rate' => 1.08]],
        ]);
        $project->budgetLines()->first()->expenses()->create([
            'description' => 'Visible expense',
            'amount' => 10,
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'amount_eur' => 10,
        ]);
        $this->actingAs($user);

        Livewire::test(ViewProjectBoard::class, ['record' => $project->id])
            ->assertSee('USD')
            ->assertDontSee('RON');
    }

    public function test_viewer_cannot_see_or_open_project_settings(): void
    {
        [$project, $viewer] = $this->projectAndUser(Project::PROJECT_ROLE_VIEWER);
        $this->actingAs($viewer);

        $this->assertFalse($project->canBeManagedBy($viewer));

        $this->get(ProjectResource::projectUrl($project, 'edit', $viewer))
            ->assertForbidden();
    }

    private function projectAndUser(string $role): array
    {
        $user = User::factory()->create();
        $owner = $role === Project::PROJECT_ROLE_VIEWER
            ? User::factory()->create()
            : $user;

        $project = Project::create([
            'owner_id' => $owner->id,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'ka_action' => 'ka152-you',
            'total_budget' => 15000,
            'first_tranche_pct' => 80,
            'withholding_tax_rate' => 10,
        ]);

        $project->budgetLines()->create([
            'title' => 'Travel',
            'emoji' => '✈️',
            'allocated_budget' => 15000,
            'sort_order' => 0,
        ]);

        if ($role === Project::PROJECT_ROLE_VIEWER) {
            $project->members()->attach($user, ['role' => $role]);
        }

        return [$project, $user];
    }
}
