<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_archive_and_restore_a_project(): void
    {
        [$project, $owner] = $this->projectAndUser();
        $this->actingAs($owner);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertActionDoesNotExist('archiveProject')
            ->assertDontSee('Archive project');

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->assertActionVisible('delete')
            ->callAction('delete');

        $this->assertSoftDeleted($project);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'deleted',
            'description' => 'archived the project',
        ]);

        Livewire::test(ListProjects::class)
            ->set('archived', true)
            ->assertSee('Archive Candidate')
            ->assertSee('Restore')
            ->call('restoreProject', $project->id)
            ->assertDontSee('Archive Candidate');

        $this->assertNotSoftDeleted($project);
        $this->assertTrue(ProjectActivityLog::query()
            ->where('project_id', $project->id)
            ->where('event', 'restored')
            ->where('description', 'restored the project')
            ->exists());
    }

    public function test_archive_is_read_only_for_viewers(): void
    {
        [$project, $viewer] = $this->projectAndUser(Project::PROJECT_ROLE_VIEWER);
        $project->delete();
        $this->actingAs($viewer);

        Livewire::test(ListProjects::class)
            ->set('archived', true)
            ->assertSee('Archive Candidate')
            ->assertDontSee('Restore');
    }

    public function test_project_editor_cannot_archive_or_restore_a_project(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'workspace_id' => null,
            'access_mode' => 'restricted',
            'name' => 'Editor Archive Candidate',
            'status' => 'writing',
        ]);
        $project->members()->attach($editor, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->actingAs($editor);

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->assertActionHidden('delete');

        $project->delete();

        Livewire::test(ListProjects::class)
            ->set('archived', true)
            ->assertSee('Editor Archive Candidate')
            ->assertDontSee('Restore');

        Livewire::test(ListProjects::class)
            ->set('archived', true)
            ->call('restoreProject', $project->id)
            ->assertForbidden();

        $this->assertSoftDeleted($project);
    }

    public function test_archived_projects_are_isolated_from_active_and_other_account_lists(): void
    {
        [$project, $manager] = $this->projectAndUser();
        $project->delete();
        Project::create([
            'owner_id' => $manager->id,
            'workspace_id' => null,
            'access_mode' => 'restricted',
            'name' => 'Active Project',
            'status' => 'writing',
        ]);
        $otherOwner = User::factory()->create();
        $otherProject = Project::create([
            'owner_id' => $otherOwner->id,
            'workspace_id' => null,
            'access_mode' => 'restricted',
            'name' => 'Other Archived Project',
            'status' => 'writing',
        ]);
        $otherProject->delete();

        $this->actingAs($manager);

        Livewire::test(ListProjects::class)
            ->assertSee('Active Project')
            ->assertDontSee('Archive Candidate')
            ->set('archived', true)
            ->assertSee('Archive Candidate')
            ->assertDontSee('Active Project')
            ->assertDontSee('Other Archived Project');
    }

    private function projectAndUser(string $role = Project::PROJECT_ROLE_EDITOR): array
    {
        $user = User::factory()->create();
        $owner = $role === Project::PROJECT_ROLE_VIEWER
            ? User::factory()->create()
            : $user;

        $project = Project::create([
            'owner_id' => $owner->id,
            'workspace_id' => null,
            'access_mode' => 'restricted',
            'name' => 'Archive Candidate',
            'status' => 'writing',
        ]);

        if ($role === Project::PROJECT_ROLE_VIEWER) {
            $project->members()->attach($user, ['role' => Project::PROJECT_ROLE_VIEWER]);
        }

        return [$project, $user];
    }
}
