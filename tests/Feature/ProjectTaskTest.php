<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Filament\Widgets\DashboardWorkspace;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_assign_edit_complete_and_delete_a_task(): void
    {
        [$workspace, $project, $member] = $this->workspaceProjectAndUser('member');
        $assignee = User::factory()->create();
        $workspace->users()->attach($assignee, ['role' => 'viewer']);
        $this->actingAs($member);
        Filament::setTenant($workspace);

        $component = Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->call('openTaskCreate')
            ->assertSet('showTaskModal', true)
            ->set('taskTitle', 'Collect partner mandates')
            ->set('taskDescription', 'Request one signed mandate from each partner.')
            ->set('taskDueDate', today()->addDays(5)->format('Y-m-d'))
            ->set('taskAssignedTo', $assignee->id)
            ->set('taskPriority', 'high')
            ->call('saveTask')
            ->assertHasNoErrors();

        $task = ProjectTask::query()->sole();
        $this->assertSame($assignee->id, $task->assigned_to);
        $this->assertSame('high', $task->priority);
        $this->assertSame($member->id, $task->created_by);

        $component->call('openTaskEdit', $task->id)
            ->set('taskTitle', 'Collect all partner mandates')
            ->call('saveTask')
            ->call('toggleTask', $task->id);

        $this->assertSame('Collect all partner mandates', $task->fresh()->title);
        $this->assertSame('completed', $task->fresh()->status);
        $this->assertSame($member->id, $task->fresh()->completed_by);
        $this->assertNotNull($task->fresh()->completed_at);

        $component->call('deleteTask', $task->id);
        $this->assertDatabaseMissing('project_tasks', ['id' => $task->id]);
        $this->assertTrue(ProjectActivityLog::query()->where('project_id', $project->id)->where('description', 'like', '%task%')->exists());
    }

    public function test_manager_can_open_task_form_from_the_page_header(): void
    {
        [$workspace, $project, $member] = $this->workspaceProjectAndUser('member');
        $this->actingAs($member);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertActionVisible('addTask')
            ->callAction('addTask')
            ->assertSet('showTaskModal', true)
            ->assertSee('Add project task');
    }

    public function test_task_assignee_must_belong_to_the_workspace(): void
    {
        [$workspace, $project, $member] = $this->workspaceProjectAndUser('member');
        $outsider = User::factory()->create();
        $this->actingAs($member);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->set('taskTitle', 'Invalid assignment')
            ->set('taskAssignedTo', $outsider->id)
            ->call('saveTask')
            ->assertHasErrors(['taskAssignedTo']);

        $this->assertSame(0, $project->tasks()->count());
    }

    public function test_viewer_sees_tasks_without_mutation_controls(): void
    {
        [$workspace, $project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $project->tasks()->create(['title' => 'Read-only task', 'priority' => 'normal']);
        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Project tasks')
            ->assertSee('Read-only task')
            ->assertDontSee('Add task')
            ->assertDontSee('Task actions');
    }

    public function test_due_tasks_appear_in_dashboard_attention_and_milestones(): void
    {
        [$workspace, $project, $member] = $this->workspaceProjectAndUser('member');
        $project->update(['status' => 'active']);
        $project->tasks()->create([
            'title' => 'Book local transport',
            'due_date' => today()->addDays(3),
            'assigned_to' => $member->id,
            'priority' => 'high',
        ]);
        $this->actingAs($member);
        Filament::setTenant($workspace);

        Livewire::test(DashboardWorkspace::class)
            ->assertSee('Task due in 3 days: Book local transport')
            ->assertSee('Task: Book local transport');
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Task Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Task Project',
            'status' => 'writing',
        ]);

        return [$workspace, $project, $user];
    }
}
