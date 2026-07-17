<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Filament\Widgets\DashboardOverview;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_assign_edit_complete_and_delete_a_task(): void
    {
        [$project, $member] = $this->workspaceProjectAndUser('member');
        $assignee = User::factory()->create();
        $project->members()->attach($assignee, ['role' => Project::PROJECT_ROLE_VIEWER]);
        $this->actingAs($member);

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
        [$project, $member] = $this->workspaceProjectAndUser('member');
        $this->actingAs($member);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertActionVisible('addTask')
            ->callAction('addTask')
            ->assertSet('showTaskModal', true)
            ->assertSee('Add project task');
    }

    public function test_task_assignee_must_belong_to_the_workspace(): void
    {
        [$project, $member] = $this->workspaceProjectAndUser('member');
        $outsider = User::factory()->create();
        $this->actingAs($member);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->set('taskTitle', 'Invalid assignment')
            ->set('taskAssignedTo', $outsider->id)
            ->call('saveTask')
            ->assertHasErrors(['taskAssignedTo']);

        $this->assertSame(0, $project->tasks()->count());
    }

    public function test_viewer_sees_tasks_without_mutation_controls(): void
    {
        [$project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $project->tasks()->create(['title' => 'Read-only task', 'priority' => 'normal']);
        $this->actingAs($viewer);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Project tasks')
            ->assertSee('Read-only task')
            ->assertDontSee('Add task')
            ->assertDontSee('Task actions');
    }

    public function test_due_tasks_appear_in_dashboard_attention_and_milestones(): void
    {
        [$project, $member] = $this->workspaceProjectAndUser('member');
        $project->update(['status' => 'active']);
        $project->tasks()->create([
            'title' => 'Book local transport',
            'due_date' => today()->addDays(3),
            'assigned_to' => $member->id,
            'priority' => 'high',
        ]);
        $this->actingAs($member);

        Livewire::test(DashboardOverview::class)
            ->assertSee('Task due in 3 days: Book local transport')
            ->assertSee('Task: Book local transport');
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $role === 'member' ? null : User::factory()->create()->id,
            'name' => 'Task Project',
            'status' => 'writing',
        ]);
        $projectRole = $role === 'viewer' ? Project::PROJECT_ROLE_VIEWER : Project::PROJECT_ROLE_EDITOR;
        $project->members()->attach($user, ['role' => $projectRole]);

        return [$project, $user];
    }
}
