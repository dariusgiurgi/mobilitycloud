<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_owner_can_archive_and_restore_a_project(): void
    {
        [$workspace, $project, $owner] = $this->workspaceProjectAndUser('owner');
        $this->actingAs($owner);
        Filament::setTenant($workspace);

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
        [$workspace, $project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $project->delete();
        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(ListProjects::class)
            ->set('archived', true)
            ->assertSee('Archive Candidate')
            ->assertDontSee('Restore');
    }

    public function test_project_editor_cannot_archive_or_restore_a_project(): void
    {
        $workspace = Workspace::create(['name' => 'Editor Archive Workspace']);
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $workspace->users()->attach($owner, ['role' => 'owner']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Editor Archive Candidate',
            'status' => 'writing',
        ]);
        $project->members()->attach($editor, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->actingAs($editor);
        Filament::setTenant($workspace);

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

    public function test_archived_projects_are_isolated_from_active_and_other_workspace_lists(): void
    {
        [$workspace, $project, $manager] = $this->workspaceProjectAndUser('owner');
        $project->delete();
        Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Active Project',
            'status' => 'writing',
        ]);
        $otherWorkspace = Workspace::create(['name' => 'Other Workspace']);
        $otherProject = Project::create([
            'workspace_id' => $otherWorkspace->id,
            'name' => 'Other Archived Project',
            'status' => 'writing',
        ]);
        $otherProject->delete();

        $this->actingAs($manager);
        Filament::setTenant($workspace);

        Livewire::test(ListProjects::class)
            ->assertSee('Active Project')
            ->assertDontSee('Archive Candidate')
            ->set('archived', true)
            ->assertSee('Archive Candidate')
            ->assertDontSee('Active Project')
            ->assertDontSee('Other Archived Project');
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Archive Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Archive Candidate',
            'status' => 'writing',
        ]);

        return [$workspace, $project, $user];
    }
}
