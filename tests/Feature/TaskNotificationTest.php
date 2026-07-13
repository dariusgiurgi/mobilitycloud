<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'sync']);
    }

    public function test_assignee_receives_an_in_app_notification_for_a_new_task(): void
    {
        [$project, $manager] = $this->projectAndManager();
        $assignee = User::factory()->create();
        $project->members()->attach($assignee, ['role' => Project::PROJECT_ROLE_VIEWER]);
        $this->actingAs($manager);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->set('taskTitle', 'Collect partner mandates')
            ->set('taskDueDate', today()->addDays(5)->format('Y-m-d'))
            ->set('taskAssignedTo', $assignee->id)
            ->call('saveTask')
            ->assertHasNoErrors();

        $notification = $assignee->notifications()->sole();
        $this->assertSame('New task assigned', $notification->data['title']);
        $this->assertStringContainsString('Collect partner mandates', $notification->data['body']);
        $this->assertNotEmpty($notification->data['actions']);
    }

    public function test_reminder_command_notifies_once_for_due_and_overdue_tasks(): void
    {
        [$project, $manager] = $this->projectAndManager();
        $dueSoon = $project->tasks()->create([
            'title' => 'Confirm transport',
            'due_date' => today()->addDays(2),
            'assigned_to' => $manager->id,
        ]);
        $overdue = $project->tasks()->create([
            'title' => 'Upload mandates',
            'due_date' => today()->subDay(),
            'assigned_to' => $manager->id,
        ]);

        $this->artisan('tasks:send-reminders')
            ->expectsOutput('2 task notifications queued.')
            ->assertSuccessful();

        $this->assertNotNull($dueSoon->fresh()->reminder_sent_at);
        $this->assertNotNull($overdue->fresh()->overdue_notified_at);
        $this->assertSame(2, $manager->notifications()->count());
        $this->assertEqualsCanonicalizing(
            ['Task deadline approaching', 'Task is overdue'],
            $manager->notifications()->get()->pluck('data.title')->all(),
        );

        $this->artisan('tasks:send-reminders')
            ->expectsOutput('0 task notifications queued.')
            ->assertSuccessful();
        $this->assertSame(2, $manager->notifications()->count());
    }

    public function test_panel_exposes_the_database_notification_center(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasDatabaseNotifications());
        $this->assertNull($panel->getDatabaseNotificationsPollingInterval());
    }

    private function projectAndManager(): array
    {
        $manager = User::factory()->create();
        $project = Project::create([
            'owner_id' => $manager->id,
            'workspace_id' => null,
            'name' => 'Notification Project',
            'status' => 'active',
        ]);

        return [$project, $manager];
    }
}
