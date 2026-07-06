<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ViewProjectBoard;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AccountWorkspaceService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_grouped_by_operational_impact(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('owner');
        $this->actingAs($user);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($user));

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->assertSee('Project identity')
            ->assertSee('Application setup')
            ->assertSee('Application template')
            ->assertSee('Timeline')
            ->assertSee('Involved organisations')
            ->assertSee('Funding and taxation')
            ->assertSee('Project currencies')
            ->assertSee('Advanced controls')
            ->assertSee('More actions');
    }

    public function test_settings_validate_dates_and_financial_percentages(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser(Project::PROJECT_ROLE_EDITOR);
        $this->actingAs($user);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($user));

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->fillForm([
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-01',
                'mobility_start_date' => '2026-07-10',
                'mobility_end_date' => '2026-07-01',
                'first_tranche_pct' => 110,
                'withholding_tax_rate' => 101,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'end_date' => 'after_or_equal',
                'mobility_end_date' => 'after_or_equal',
                'first_tranche_pct' => 'max',
                'withholding_tax_rate' => 'max',
            ]);
    }

    public function test_expense_prefix_is_saved_in_a_consistent_format(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser(Project::PROJECT_ROLE_EDITOR);
        $this->actingAs($user);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($user));

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->fillForm(['expense_prefix' => 'eras-26'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('ERAS-26', $project->fresh()->expense_prefix);
    }

    public function test_application_template_is_saved_as_normalised_action_key(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser(Project::PROJECT_ROLE_EDITOR);
        $this->actingAs($user);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($user));

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->assertFormSet(['ka_action' => 'ka152-you'])
            ->fillForm(['ka_action' => 'ka153-you'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('ka153-you', $project->fresh()->ka_action);
    }

    public function test_application_template_can_be_cleared_for_manual_projects(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser(Project::PROJECT_ROLE_EDITOR);
        $this->actingAs($user);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($user));

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->fillForm(['ka_action' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($project->fresh()->ka_action);
    }

    public function test_project_settings_manage_project_currencies_and_recalculate_project_expenses(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser(Project::PROJECT_ROLE_EDITOR);
        $otherProject = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Other project',
            'status' => 'active',
            'currencies' => [['code' => 'RON', 'rate' => 5.07]],
        ]);
        $project->update(['currencies' => [['code' => 'RON', 'rate' => 5.07]]]);
        $expense = $project->budgetLines()->first()->expenses()->create([
            'description' => 'Venue',
            'amount' => 507,
            'currency' => 'RON',
            'exchange_rate' => 5.07,
            'amount_eur' => 100,
        ]);
        $otherExpense = $otherProject->budgetLines()->first()->expenses()->create([
            'description' => 'Other venue',
            'amount' => 507,
            'currency' => 'RON',
            'exchange_rate' => 5.07,
            'amount_eur' => 100,
        ]);

        $this->actingAs($user);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($user));

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

    public function test_budget_uses_project_currencies_not_workspace_currencies(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser(Project::PROJECT_ROLE_EDITOR);
        $workspace->update(['currencies' => ['RON' => 5.07]]);
        $project->update(['currencies' => [['code' => 'USD', 'rate' => 1.08]]]);
        $project->budgetLines()->first()->expenses()->create([
            'description' => 'Visible expense',
            'amount' => 10,
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'amount_eur' => 10,
        ]);
        $this->actingAs($user);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($user));

        Livewire::test(ViewProjectBoard::class, ['record' => $project->id])
            ->assertSee('USD')
            ->assertDontSee('RON');
    }

    public function test_viewer_cannot_see_or_open_project_settings(): void
    {
        [$workspace, $project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $this->actingAs($viewer);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($viewer));

        $this->assertFalse($project->canBeManagedBy($viewer));

        $this->get(ProjectResource::projectUrl($project, 'edit', $viewer))
            ->assertNotFound();
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Settings Workspace']);
        $user = User::factory()->create();
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'ka_action' => 'ka152',
            'total_budget' => 15000,
            'first_tranche_pct' => 80,
            'withholding_tax_rate' => 10,
        ]);

        if (in_array($role, [Project::PROJECT_ROLE_EDITOR, Project::PROJECT_ROLE_VIEWER], true)) {
            $project->members()->attach($user, ['role' => $role]);
        } else {
            $workspace->users()->attach($user, ['role' => $role]);
        }

        return [$workspace, $project, $user];
    }
}
