<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\WriteApplication;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WriteApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_workspace_shows_progress_and_outline(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->createSection($project, 'Objectives', 'A focused project answer.', 100, 'Context', 0);
        $this->createSection($project, 'Impact', '', 100, 'Impact', 1);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Application workspace')
            ->assertSee('50% drafted')
            ->assertSee('1 of 2 sections')
            ->assertSee('Application outline')
            ->assertSee('Objectives')
            ->assertSee('Impact')
            ->assertSee('Export PDF');
    }

    public function test_manager_changes_are_saved_to_the_application(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $section = $this->createSection($project, 'Objectives', '', 100, 'Context', 0);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->set("content.{$section->id}", 'New application answer')
            ->assertSee('100% drafted');

        $this->assertSame('New application answer', $section->fresh()->content);
    }

    public function test_viewer_gets_a_clean_read_only_workspace(): void
    {
        [$workspace, $project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $section = $this->createSection($project, 'Objectives', 'Existing answer', 100, 'Context', 0);

        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Read-only access')
            ->assertSet("content.{$section->id}", 'Existing answer')
            ->assertDontSee('Load template')
            ->assertDontSee('Add section')
            ->assertDontSee('Insert from library');
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Application Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'ka_action' => 'ka152',
        ]);

        return [$workspace, $project, $user];
    }

    private function createSection(Project $project, string $title, string $content, int $limit, string $category, int $order): ProjectApplicationSection
    {
        return ProjectApplicationSection::create([
            'project_id' => $project->id,
            'title' => $title,
            'content' => $content,
            'char_limit' => $limit,
            'category' => $category,
            'sort_order' => $order,
        ]);
    }
}
