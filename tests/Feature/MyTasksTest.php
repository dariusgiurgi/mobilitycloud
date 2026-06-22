<?php

namespace Tests\Feature;

use App\Filament\Pages\MyTasks;
use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_shows_only_tasks_assigned_to_user_in_current_workspace(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('viewer');
        $otherUser = User::factory()->create();
        $workspace->users()->attach($otherUser, ['role' => 'member']);

        $project->tasks()->create(['title' => 'My visible task', 'assigned_to' => $user->id]);
        $project->tasks()->create(['title' => 'Someone else task', 'assigned_to' => $otherUser->id]);

        $otherWorkspace = Workspace::create(['name' => 'Other Workspace']);
        $otherWorkspace->users()->attach($user, ['role' => 'viewer']);
        $otherProject = Project::create(['workspace_id' => $otherWorkspace->id, 'name' => 'Other Project', 'status' => 'active']);
        $otherProject->tasks()->create(['title' => 'Other workspace task', 'assigned_to' => $user->id]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(MyTasks::class)
            ->assertSee('My visible task')
            ->assertDontSee('Someone else task')
            ->assertDontSee('Other workspace task');
    }

    public function test_stats_search_and_due_filters_reflect_assigned_tasks(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $project->tasks()->create(['title' => 'Overdue mandate', 'assigned_to' => $user->id, 'due_date' => today()->subDay()]);
        $project->tasks()->create(['title' => 'Book transport', 'assigned_to' => $user->id, 'due_date' => today()->addDays(3)]);
        $project->tasks()->create(['title' => 'No deadline note', 'assigned_to' => $user->id]);
        $project->tasks()->create(['title' => 'Completed task', 'assigned_to' => $user->id, 'status' => 'completed', 'completed_at' => now()]);
        $this->actingAs($user);
        Filament::setTenant($workspace);

        $component = Livewire::test(MyTasks::class)
            ->assertSee('Overdue mandate')
            ->assertSee('Book transport')
            ->assertSee('No deadline note')
            ->assertDontSee('Completed task');

        $this->assertSame([
            'open' => 3,
            'overdue' => 1,
            'next_seven_days' => 1,
            'completed' => 1,
        ], $component->instance()->getStats());

        $component->set('dueFilter', 'overdue')
            ->assertSee('Overdue mandate')
            ->assertDontSee('Book transport');

        $component->set('dueFilter', 'all')
            ->set('search', 'transport')
            ->assertSee('Book transport')
            ->assertDontSee('Overdue mandate');
    }

    public function test_viewer_can_complete_own_task_from_global_and_project_views(): void
    {
        [$workspace, $project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $task = $project->tasks()->create(['title' => 'Viewer responsibility', 'assigned_to' => $viewer->id]);
        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(MyTasks::class)->call('toggleTask', $task->id);
        $this->assertSame('completed', $task->fresh()->status);
        $this->assertSame($viewer->id, $task->fresh()->completed_by);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->set('taskFilter', 'completed')
            ->assertSee('Viewer responsibility')
            ->call('toggleTask', $task->id);
        $this->assertSame('open', $task->fresh()->status);
    }

    public function test_navigation_badge_counts_open_assigned_tasks_and_marks_overdue(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $project->tasks()->create(['title' => 'Open', 'assigned_to' => $user->id]);
        $project->tasks()->create(['title' => 'Late', 'assigned_to' => $user->id, 'due_date' => today()->subDay()]);
        $project->tasks()->create(['title' => 'Done', 'assigned_to' => $user->id, 'status' => 'completed']);
        $this->actingAs($user);
        Filament::setTenant($workspace);

        $this->assertSame('2', MyTasks::getNavigationBadge());
        $this->assertSame('danger', MyTasks::getNavigationBadgeColor());
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Tasks Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);

        return [$workspace, $project, $user];
    }
}
