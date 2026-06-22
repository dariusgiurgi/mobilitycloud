<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\WriteApplication;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectApplicationVersion;
use App\Models\User;
use App\Models\Workspace;
use App\Support\ApplicationTemplates;
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

    public function test_official_youth_templates_are_versioned_and_action_specific(): void
    {
        $this->assertSame(2026, ApplicationTemplates::get('ka152')['call_year']);
        $this->assertSame('KA153-YOU', ApplicationTemplates::get('ka153-you')['action']);
        $this->assertArrayHasKey('ka154-you', ApplicationTemplates::list());
        $this->assertArrayHasKey('ka155-you', ApplicationTemplates::list());
        $this->assertGreaterThan(15, count(ApplicationTemplates::sections('ka152-you')));
    }

    public function test_template_sync_preserves_existing_answers_and_creates_backup(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $legacy = $this->createSection($project, 'My custom answer', 'Do not delete this text.', 1000, 'Custom', 0);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->set('selectedTemplate', 'ka152-you')
            ->call('loadTemplate')
            ->assertSee('Writing guidance');

        $this->assertSame('Do not delete this text.', $legacy->fresh()->content);
        $this->assertSame('ka152-you', $project->fresh()->ka_action);
        $this->assertGreaterThan(15, $project->applicationSections()->count());
        $this->assertSame(1, ProjectApplicationVersion::where('project_id', $project->id)->count());
    }

    public function test_named_version_can_restore_a_previous_draft(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $section = $this->createSection($project, 'Objectives', 'Original draft', 1000, 'Context', 0);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->set('versionLabel', 'Partner review')
            ->call('saveVersion');

        $version = ProjectApplicationVersion::where('project_id', $project->id)->firstOrFail();
        $section->update(['content' => 'Changed later']);

        $component->call('restoreVersion', $version->id);

        $this->assertSame('Original draft', $project->applicationSections()->sole()->content);
        $this->assertSame(2, ProjectApplicationVersion::where('project_id', $project->id)->count());
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
