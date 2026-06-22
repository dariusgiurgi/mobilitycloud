<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_project_changes_are_recorded_with_the_actor(): void
    {
        [$workspace, $user] = $this->workspaceAndUser();
        $this->actingAs($user);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Activity Project',
            'status' => 'writing',
        ]);

        $project->update(['status' => 'submitted']);
        $participant = Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Ana',
            'last_name' => 'Pop',
            'email' => 'ana@example.org',
            'role' => 'participant',
        ]);
        $participant->update(['phone' => '+40123456789']);
        $participant->delete();

        $entries = ProjectActivityLog::query()->where('project_id', $project->id)->get();
        $this->assertCount(5, $entries);
        $this->assertTrue($entries->every(fn (ProjectActivityLog $entry): bool => $entry->user_id === $user->id));
        $this->assertTrue($entries->contains(fn (ProjectActivityLog $entry): bool => $entry->event === 'status_changed' && str_contains($entry->description, 'Submitted')));
        $this->assertTrue($entries->contains(fn (ProjectActivityLog $entry): bool => $entry->event === 'created' && str_contains($entry->description, 'Ana Pop')));

        $participantUpdate = $entries->first(fn (ProjectActivityLog $entry): bool => $entry->event === 'updated' && str_contains($entry->description, 'Ana Pop'));
        $this->assertSame(['phone'], $participantUpdate->metadata['changed_fields']);
        $this->assertStringNotContainsString('+40123456789', json_encode($participantUpdate->metadata));
    }

    public function test_recent_activity_is_visible_on_project_overview(): void
    {
        [$workspace, $user] = $this->workspaceAndUser();
        $this->actingAs($user);
        Filament::setTenant($workspace);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Visible Activity',
            'status' => 'writing',
        ]);
        Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Mara',
            'last_name' => 'Ionescu',
            'role' => 'participant',
        ]);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Recent activity')
            ->assertSee($user->name)
            ->assertSee('added participant')
            ->assertSee('Mara Ionescu');
    }

    public function test_activity_is_removed_cleanly_when_project_is_force_deleted(): void
    {
        [$workspace, $user] = $this->workspaceAndUser();
        $this->actingAs($user);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Temporary Project',
            'status' => 'writing',
        ]);

        $project->forceDelete();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('project_activity_logs', ['project_id' => $project->id]);
    }

    private function workspaceAndUser(): array
    {
        $workspace = Workspace::create(['name' => 'Activity Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        return [$workspace, $user];
    }
}
