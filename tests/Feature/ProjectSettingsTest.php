<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_grouped_by_operational_impact(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->assertSee('Project identity')
            ->assertSee('Timeline')
            ->assertSee('Involved organisations')
            ->assertSee('Funding and taxation')
            ->assertSee('Advanced controls')
            ->assertSee('More actions');
    }

    public function test_settings_validate_dates_and_financial_percentages(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);
        Filament::setTenant($workspace);

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
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->fillForm(['expense_prefix' => 'eras-26'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('ERAS-26', $project->fresh()->expense_prefix);
    }

    public function test_viewer_cannot_see_or_open_project_settings(): void
    {
        [$workspace, $project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertDontSee('Settings');

        $this->get(ProjectResource::getUrl('edit', ['record' => $project]))
            ->assertForbidden();
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Settings Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'ka_action' => 'ka152',
            'total_budget' => 15000,
            'first_tranche_pct' => 80,
            'withholding_tax_rate' => 10,
        ]);

        return [$workspace, $project, $user];
    }
}
