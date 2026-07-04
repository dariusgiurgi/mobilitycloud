<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
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

    public function test_only_workspace_owner_sees_create_project_action(): void
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
        Livewire::test(ListProjects::class)->assertDontSee('New project');
    }
}
