<?php

namespace Tests\Feature;

use App\Filament\Pages\WorkspaceCalendar;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_combines_accessible_project_mobility_and_task_dates(): void
    {
        $workspace = Workspace::create(['name' => 'Calendar Workspace']);
        $viewer = User::factory()->create();
        $workspace->users()->attach($viewer, ['role' => 'viewer']);
        $visible = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Visible Mobility',
            'status' => 'active',
            'start_date' => '2026-07-03',
            'mobility_start_date' => '2026-07-10',
        ]);
        $visible->tasks()->create(['title' => 'Book venue', 'due_date' => '2026-07-08']);
        Project::create([
            'workspace_id' => $workspace->id,
            'access_mode' => 'restricted',
            'name' => 'Hidden Mobility',
            'status' => 'active',
            'start_date' => '2026-07-04',
        ]);

        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(WorkspaceCalendar::class)
            ->set('month', '2026-07')
            ->assertSee('Visible Mobility')
            ->assertSee('Mobility starts')
            ->assertSee('Book venue')
            ->assertDontSee('Hidden Mobility');
    }
}
