<?php

namespace Tests\Feature;

use App\Filament\Pages\ProjectCalendar;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_combines_accessible_project_mobility_and_task_dates(): void
    {
        $viewer = User::factory()->create();
        $visible = Project::create([
            'owner_id' => $viewer->id,
            'name' => 'Visible Mobility',
            'status' => 'active',
            'start_date' => '2026-07-03',
            'mobility_start_date' => '2026-07-10',
        ]);
        $visible->tasks()->create(['title' => 'Book venue', 'due_date' => '2026-07-08']);
        $otherUser = User::factory()->create();
        Project::create([
            'owner_id' => $otherUser->id,
            'access_mode' => 'restricted',
            'name' => 'Hidden Mobility',
            'status' => 'active',
            'start_date' => '2026-07-04',
        ]);

        $this->actingAs($viewer);

        Livewire::test(ProjectCalendar::class)
            ->set('month', '2026-07')
            ->assertSee('Visible Mobility')
            ->assertSee('Mobility starts')
            ->assertSee('Book venue')
            ->assertDontSee('Hidden Mobility');
    }
}
