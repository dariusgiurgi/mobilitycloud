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
            ->assertSee('Consistency checker')
            ->assertSee('Quality review')
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
        $this->assertNotEmpty(ApplicationTemplates::catalog());
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

    public function test_template_manager_reports_alignment_and_can_switch_catalog_template(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->createSection($project, 'Summary objectives', 'Existing answer', 100, 'Old category', 0, 'summary-objectives');
        $this->createSection($project, 'Custom evaluator note', 'Keep this custom note.', 1000, 'Custom', 1, 'custom-note');

        $this->actingAs($user);
        Filament::setTenant($workspace);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->call('openTemplateDetails')
            ->assertSee('Application template manager')
            ->assertSee('Missing official questions')
            ->call('selectTemplate', 'ka153-you')
            ->assertSet('selectedTemplate', 'ka153-you');

        $alignment = $component->instance()->getTemplateAlignment();

        $this->assertSame('ka153-you', $component->instance()->selectedTemplate);
        $this->assertGreaterThan(0, $alignment['missing_count']);
        $this->assertSame(1, $alignment['custom_count']);
        $this->assertSame(1, $alignment['matched']);
    }

    public function test_consistency_checker_flags_missing_answers_and_erasmus_themes(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->createSection($project, 'Project objectives', 'We will improve youth participation.', 1000, 'Project rationale', 0, 'needs-objectives');
        $this->createSection($project, 'Expected impact', '', 1000, 'Project management', 1, 'summary-impact');

        $this->actingAs($user);
        Filament::setTenant($workspace);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Consistency checker')
            ->assertSee('Unanswered questions')
            ->assertSee('Inclusion');

        $review = $component->instance()->getConsistencyReview();

        $this->assertGreaterThan(0, $review['critical']);
        $this->assertGreaterThan(0, $review['warning']);
        $this->assertLessThan(100, $review['score']);
        $this->assertContains('Learning recognition', collect($review['issues'])->pluck('area')->all());
    }

    public function test_quality_review_scores_evaluator_criteria(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $project->update(['total_budget' => 25000]);

        $strongAnswer = implode(' ', [
            'The needs analysis shows concrete barriers for young people with fewer opportunities and defines clear objectives linked to Erasmus priorities.',
            'Participants and target groups will co-create activities, workshops and mobility sessions using non-formal methods.',
            'Preparation, mentoring, safeguarding, insurance and risk management are planned before and during the activity.',
            'Learning outcomes will be reflected daily, documented through Youthpass recognition and transferred into local community action.',
            'Partners have clear roles, responsibilities, communication meetings, monitoring tasks, logistics and budget coordination.',
            'Evaluation indicators, feedback tools, dissemination audiences, visibility channels, sustainability and follow-up actions are defined.',
            'Green travel, sustainable choices, digital tools and virtual preparation will support implementation where useful.',
        ]);

        $this->createSection($project, 'Needs and objectives', $strongAnswer, 6000, 'Project rationale', 0, 'needs-objectives');
        $this->createSection($project, 'Impact and dissemination', $strongAnswer, 6000, 'Impact', 1, 'dissemination');

        $this->actingAs($user);
        Filament::setTenant($workspace);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Quality review')
            ->assertSee('Relevance')
            ->assertSee('Project design')
            ->assertSee('Management &amp; partnership', false)
            ->assertSee('Impact')
            ->assertSee('Inclusion &amp; sustainability', false);

        $quality = $component->instance()->getQualityReview();

        $this->assertGreaterThanOrEqual(80, $quality['score']);
        $this->assertCount(5, $quality['criteria']);
        $this->assertSame('Relevance', $quality['criteria'][0]['label']);
    }

    public function test_review_details_modal_shows_full_consistency_and_quality_checks(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->createSection($project, 'Project objectives', 'We will improve youth participation.', 1000, 'Project rationale', 0, 'needs-objectives');
        $this->createSection($project, 'Expected impact', '', 1000, 'Project management', 1, 'summary-impact');

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('View all checks')
            ->call('openReviewDetails')
            ->assertSet('showReviewDetails', true)
            ->assertSee('Application review details')
            ->assertSee('Consistency issues')
            ->assertSee('Quality criteria')
            ->assertSee('Learning recognition')
            ->assertSee('Needs are evidenced')
            ->call('closeReviewDetails')
            ->assertSet('showReviewDetails', false);
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

    private function createSection(Project $project, string $title, string $content, int $limit, string $category, int $order, ?string $questionKey = null): ProjectApplicationSection
    {
        return ProjectApplicationSection::create([
            'project_id' => $project->id,
            'question_key' => $questionKey,
            'title' => $title,
            'content' => $content,
            'char_limit' => $limit,
            'category' => $category,
            'sort_order' => $order,
        ]);
    }
}
