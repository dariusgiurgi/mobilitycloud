<?php

namespace Tests\Feature;

use App\Filament\Pages\NotificationPreferences;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\TaskNotificationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_preferences_and_suppress_selected_task_alerts(): void
    {
        $workspace = Workspace::create(['name' => 'Preferences Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Preferences Project',
            'status' => 'active',
        ]);
        $project->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);
        $task = $project->tasks()->create([
            'title' => 'Confirm venue',
            'assigned_to' => $user->id,
            'due_date' => today()->addDays(2),
        ]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(NotificationPreferences::class)
            ->set('taskAssigned', false)
            ->set('taskDueSoon', true)
            ->set('taskOverdue', false)
            ->call('save');

        $this->assertFalse($user->fresh()->wantsNotification('task_assigned'));
        $this->assertTrue($user->fresh()->wantsNotification('task_due_soon'));
        $this->assertFalse(app(TaskNotificationService::class)->sendAssignment($task));
        $this->assertTrue(app(TaskNotificationService::class)->sendDueSoon($task));
        $this->assertSame(1, $user->notifications()->count());
    }
}
