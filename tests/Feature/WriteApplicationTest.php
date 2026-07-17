<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\WriteApplication;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectApplicationVersion;
use App\Models\User;
use App\Support\ApplicationTableDefinitions;
use App\Support\ApplicationTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WriteApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_editor_shows_progress_and_compact_sidebar(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $this->createSection($project, 'Objectives', 'A focused project answer.', 100, 'Context', 0);
        $this->createSection($project, 'Impact', '', 100, 'Impact', 1);

        $this->actingAs($user);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Application workspace')
            ->assertSee('Consistency checker')
            ->assertSee('Quality review')
            ->assertSee('50% drafted')
            ->assertSee('1 of 2 sections')
            ->assertSee('Search questions or answers')
            ->assertSee('Review queue')
            ->assertSee('Export full pack')
            ->assertSee('Export Word')
            ->assertSee('More')
            ->assertSee('Export PDF');
    }

    public function test_manager_changes_are_saved_to_the_application(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $section = $this->createSection($project, 'Objectives', '', 100, 'Context', 0);

        $this->actingAs($user);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->set("content.{$section->id}", 'New application answer')
            ->assertSee('100% drafted');

        $this->assertSame('New application answer', $section->fresh()->content);
    }

    public function test_viewer_gets_a_clean_read_only_workspace(): void
    {
        [$project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $section = $this->createSection($project, 'Objectives', 'Existing answer', 100, 'Context', 0);

        $this->actingAs($viewer);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Read-only access')
            ->assertSet("content.{$section->id}", 'Existing answer')
            ->assertDontSee('Load template')
            ->assertDontSee('Add section')
            ->assertDontSee('Insert from library');
    }

    public function test_already_approved_project_without_writing_structure_hides_review_sidebar(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $project->update([
            'status' => 'approved',
            'approved_grant_amount' => 9900,
            'approved_budget' => 9900,
            'approved_declared_at' => now(),
            'activation_fee_amount' => 100,
            'invoice_status' => Project::INVOICE_PENDING,
        ]);

        $this->actingAs($user);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Application not required')
            ->assertSee('Writing is not required for this project')
            ->assertSee('No submission checklist or quality score is shown')
            ->assertDontSee('Official readiness')
            ->assertDontSee('Submission checklist')
            ->assertDontSee('Quality review')
            ->assertDontSee('Start the application structure');
    }

    public function test_official_youth_templates_are_versioned_and_action_specific(): void
    {
        $this->assertSame(2026, ApplicationTemplates::get('ka152')['call_year']);
        $this->assertSame('KA153-YOU', ApplicationTemplates::get('ka153-you')['action']);
        $this->assertArrayHasKey('ka151-you', ApplicationTemplates::list());
        $this->assertArrayHasKey('ka152-you', ApplicationTemplates::list());
        $this->assertArrayHasKey('ka153-you', ApplicationTemplates::list());
        $this->assertArrayHasKey('ka154-you', ApplicationTemplates::list());
        $this->assertArrayHasKey('ka121-sch', ApplicationTemplates::list());
        $this->assertArrayHasKey('ka122-vet', ApplicationTemplates::list());
        $this->assertArrayHasKey('ka210-adu', ApplicationTemplates::list());
        $this->assertArrayHasKey('ka220-vet', ApplicationTemplates::list());
        $this->assertSame('KA121-SCH', ApplicationTemplates::get('ka121-sch')['action']);
        $this->assertSame('KA122-VET', ApplicationTemplates::get('ka122-vet')['action']);
        $this->assertSame('KA210-ADU', ApplicationTemplates::get('ka210-adu')['action']);
        $this->assertSame('KA220-YOU', ApplicationTemplates::get('ka220-you')['action']);
        $this->assertTrue(ApplicationTemplates::isOfficiallyVerified('ka151-you'));
        $this->assertTrue(ApplicationTemplates::isOfficiallyVerified('ka152-you'));
        $this->assertTrue(ApplicationTemplates::isOfficiallyVerified('ka153-you'));
        $this->assertTrue(ApplicationTemplates::isOfficiallyVerified('ka154-you'));
        $this->assertTrue(ApplicationTemplates::isOfficiallyVerified('ka121-sch'));
        $this->assertTrue(ApplicationTemplates::isOfficiallyVerified('ka122-vet'));
        $this->assertTrue(ApplicationTemplates::isOfficiallyVerified('ka210-adu'));
        $this->assertTrue(ApplicationTemplates::isOfficiallyVerified('ka220-vet'));
        $this->assertCount(3, ApplicationTemplates::sections('ka151-you'));
        $this->assertSame(
            'Do you foresee Virtual/Blended activities and/or the use of any virtual component, before, during or after the activity?',
            ApplicationTemplates::sections('ka151-you')[1]['title'],
        );
        $this->assertCount(41, ApplicationTemplates::sections('ka152-you'));
        $this->assertSame(
            'Please describe the background of the participants in each participating group and how each group was formed. Please also provide information on the group leaders, the age of the participants and how country balance is ensured. If necessary, explain how the gender balance is respected.',
            collect(ApplicationTemplates::sections('ka152-you'))->firstWhere('key', 'activity-participant-background')['title'],
        );
        $this->assertSame(
            'If any, please explain the particular measures (accompanying person, reinforced preparation etc.) you will put in place to cater for the specific needs of these participants and/or to support their participation.',
            collect(ApplicationTemplates::sections('ka152-you'))->firstWhere('key', 'fewer-opportunities-measures')['title'],
        );
        $this->assertCount(31, ApplicationTemplates::sections('ka153-you'));
        $this->assertCount(35, ApplicationTemplates::sections('ka154-you'));
        $this->assertCount(22, ApplicationTemplates::sections('ka122-sch'));
        $this->assertCount(4, ApplicationTemplates::sections('ka121-adu'));
        $this->assertCount(18, ApplicationTemplates::sections('ka210-you'));
        $this->assertCount(37, ApplicationTemplates::sections('ka220-vet'));
        $this->assertCount(18, ApplicationTemplates::catalog());
        $this->assertSame(['any', 'ka122-sch', 'ka122'], ApplicationTemplates::libraryKeys('ka122-sch'));
    }

    public function test_verified_template_catalog_is_complete_unique_and_table_safe(): void
    {
        $templates = ApplicationTemplates::verifiedTemplates();

        $this->assertNotEmpty($templates);

        foreach ($templates as $templateKey => $template) {
            $this->assertNotEmpty($template['label'], "Template {$templateKey} is missing a label.");
            $this->assertNotEmpty($template['action'], "Template {$templateKey} is missing an action.");
            $this->assertNotEmpty($template['call_year'], "Template {$templateKey} is missing a call year.");
            $this->assertNotEmpty($template['source_url'], "Template {$templateKey} is missing a source URL.");
            $this->assertNotEmpty($template['sections'], "Template {$templateKey} has no official questions.");

            $questionKeys = collect($template['sections'])->pluck('key')->all();
            $this->assertSame(
                count($questionKeys),
                count(array_unique($questionKeys)),
                "Template {$templateKey} has duplicate question keys.",
            );

            foreach ($template['sections'] as $index => $section) {
                $questionNumber = $index + 1;

                $this->assertNotEmpty($section['key'] ?? null, "Template {$templateKey} question {$questionNumber} has no key.");
                $this->assertNotEmpty($section['category'] ?? null, "Template {$templateKey} question {$questionNumber} has no category.");
                $this->assertNotEmpty($section['title'] ?? null, "Template {$templateKey} question {$questionNumber} has no title.");
                $this->assertSame(trim($section['title']), $section['title'], "Template {$templateKey} question {$questionNumber} has unsafe title whitespace.");

                if (array_key_exists('char_limit', $section) && $section['char_limit'] !== null) {
                    $this->assertIsInt($section['char_limit'], "Template {$templateKey} question {$questionNumber} character limit must be an integer.");
                    $this->assertGreaterThan(0, $section['char_limit'], "Template {$templateKey} question {$questionNumber} character limit must be positive.");
                }

                $tables = ApplicationTableDefinitions::forSection(new ProjectApplicationSection([
                    'question_key' => $section['key'],
                    'title' => $section['title'],
                    'category' => $section['category'] ?? null,
                ]));

                foreach ($tables as $table) {
                    $this->assertNotEmpty($table['key'], "Template {$templateKey} question {$questionNumber} has a table without key.");
                    $this->assertNotEmpty($table['label'], "Template {$templateKey} question {$questionNumber} has a table without label.");
                    $this->assertNotEmpty($table['columns'], "Template {$templateKey} question {$questionNumber} table {$table['key']} has no columns.");

                    foreach ($table['columns'] as $column) {
                        $this->assertNotEmpty($column['field'], "Template {$templateKey} table {$table['key']} has a column without field.");
                        $this->assertNotEmpty($column['label'], "Template {$templateKey} table {$table['key']} has a column without label.");
                    }
                }
            }
        }

        $ka152TableLabels = collect(ApplicationTemplates::sections('ka152-you'))
            ->flatMap(fn (array $section) => ApplicationTableDefinitions::forSection(new ProjectApplicationSection([
                'question_key' => $section['key'],
                'title' => $section['title'],
                'category' => $section['category'] ?? null,
            ])))
            ->pluck('label')
            ->unique()
            ->values()
            ->all();

        $this->assertContains('Project topics', $ka152TableLabels);
        $this->assertContains('Participant groups', $ka152TableLabels);
        $this->assertContains('Dissemination plan', $ka152TableLabels);

        $ka151TableLabels = collect(ApplicationTemplates::sections('ka151-you'))
            ->flatMap(fn (array $section) => ApplicationTableDefinitions::forSection(new ProjectApplicationSection([
                'question_key' => $section['key'],
                'title' => $section['title'],
                'category' => $section['category'] ?? null,
            ])))
            ->pluck('label')
            ->unique()
            ->values()
            ->all();

        $this->assertContains('Additional funding needs', $ka151TableLabels);
    }

    public function test_template_sync_preserves_existing_answers_and_creates_backup(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $legacy = $this->createSection($project, 'My custom answer', 'Do not delete this text.', 1000, 'Custom', 0);

        $this->actingAs($user);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->set('selectedTemplate', 'ka151-you')
            ->call('loadTemplate')
            ->assertSee('Writing guidance');

        $this->assertSame('Do not delete this text.', $legacy->fresh()->content);
        $this->assertSame('ka151-you', $project->fresh()->ka_action);
        $this->assertSame(4, $project->applicationSections()->count());
        $this->assertSame(1, ProjectApplicationVersion::where('project_id', $project->id)->count());
    }

    public function test_named_version_can_restore_a_previous_draft(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $section = $this->createSection($project, 'Objectives', 'Original draft', 1000, 'Context', 0);

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->set('versionLabel', 'Partner review')
            ->call('saveVersion');

        $version = ProjectApplicationVersion::where('project_id', $project->id)->firstOrFail();
        $section->update(['content' => 'Changed later']);

        $component
            ->set('showVersions', true)
            ->call('openVersionDiff', $version->id)
            ->assertSet('versionDiffId', $version->id)
            ->assertSee('Comparing with')
            ->assertSee('Modified');

        $diff = $component->instance()->getVersionDiff();

        $this->assertSame(1, $diff['summary']['modified']);
        $this->assertSame('Answer', $diff['changes'][0]['fields'][0]);
        $this->assertStringContainsString('Original draft', $diff['changes'][0]['before']);
        $this->assertStringContainsString('Changed later', $diff['changes'][0]['after']);

        $component->call('restoreVersion', $version->id);

        $this->assertSame('Original draft', $project->applicationSections()->sole()->content);
        $this->assertSame(2, ProjectApplicationVersion::where('project_id', $project->id)->count());
        $this->assertNull($component->instance()->versionDiffId);
    }

    public function test_template_manager_reports_alignment_and_can_switch_catalog_template(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $this->createSection($project, 'Summary objectives', 'Existing answer', 100, 'Old category', 0, 'summary-objectives');
        $this->createSection($project, 'Custom evaluator note', 'Keep this custom note.', 1000, 'Custom', 1, 'custom-note');

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->call('openTemplateDetails')
            ->assertSee('Application template manager')
            ->assertSee('Youth mobility')
            ->assertSee('Search by KA code, sector, form or keyword')
            ->assertSee('Officially verified')
            ->assertSee('Switch impact preview')
            ->assertSee('Template audit')
            ->assertSee('Audit score')
            ->assertSee('Missing official questions')
            ->set('templateCatalogSearch', '153')
            ->assertSee('KA153-YOU')
            ->assertSee('Audit')
            ->call('selectTemplate', 'ka153-you')
            ->assertSet('selectedTemplate', 'ka153-you');

        $alignment = $component->instance()->getTemplateAlignment();
        $catalogKeys = collect($component->instance()->getTemplateCatalog())->pluck('key')->all();
        $audit = $component->instance()->getSelectedTemplateAudit();
        $auditSummary = $component->instance()->getTemplateAuditSummary();

        $this->assertSame('ka153-you', $component->instance()->selectedTemplate);
        $this->assertContains('ka153-you', $catalogKeys);
        $this->assertNotContains('ka152-you', $catalogKeys);
        $this->assertGreaterThan(0, $alignment['missing_count']);
        $this->assertSame(1, $alignment['custom_count']);
        $this->assertSame(1, $alignment['matched']);
        $this->assertGreaterThan(0, $audit['counts']['sections']);
        $this->assertArrayHasKey('tables', $audit);
        $this->assertGreaterThanOrEqual(1, $auditSummary['templates']);
    }

    public function test_template_preview_hints_and_activity_table_mode_are_visible(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $project->update(['ka_action' => 'ka151-you']);
        $this->createSection($project, 'Do you foresee Virtual/Blended activities and/or the use of any virtual component, before, during or after the activity?', 'We will use online preparation.', 4000, 'Summary', 0, 'virtual-blended-components');

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Activity/table-driven application')
            ->assertSee('Evaluator hints for this question')
            ->call('openTemplateDetails')
            ->assertSee('Officially verified')
            ->assertSee('Switch impact preview');

        $this->assertTrue($component->instance()->isActivityTableTemplate());
        $this->assertGreaterThanOrEqual(1, $component->instance()->getTemplateSwitchPreview()['matched_count']);
        $this->assertNotEmpty($component->instance()->getQuestionHints($project->applicationSections()->first()));
    }

    public function test_standard_application_tables_can_be_filled_and_exported(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $section = $this->createSection(
            $project,
            'Have you, at this stage, identified the need of any specific additional funding such as Exceptional costs for expensive travel, visas, financial guarantee, or Inclusion support for participants etc.? If this is the case, please fill in the table below.',
            'Yes, we need inclusion support.',
            1000,
            'Activities',
            0,
            'additional-funding-needs',
        );

        $this->actingAs($user);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Additional funding needs')
            ->call('addTableRow', $section->id, 'additional_funding')
            ->set("tables.{$section->id}.additional_funding.0.cost_type", 'Inclusion support')
            ->set("tables.{$section->id}.additional_funding.0.participants", '4 participants')
            ->set("tables.{$section->id}.additional_funding.0.estimated_cost", '800 EUR');

        $section->refresh();

        $this->assertSame('Inclusion support', $section->application_tables['additional_funding'][0]['cost_type']);
        $this->assertSame('4 participants', $section->application_tables['additional_funding'][0]['participants']);

        $this->get(route('projects.export-application-word', $project))
            ->assertOk()
            ->assertSee('Additional funding needs')
            ->assertSee('Inclusion support')
            ->assertSee('800 EUR');
    }

    public function test_standard_application_tables_can_be_populated_from_project_data(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $project->update([
            'mobility_start_date' => '2026-08-01',
            'mobility_end_date' => '2026-08-08',
            'partner_orgs' => [
                ['name' => 'Scoala de Jocuri', 'country' => 'Romania', 'oid' => null],
            ],
        ]);

        $project->participants()->create([
            'first_name' => 'Ana',
            'last_name' => 'Pop',
            'role' => 'participant',
            'fewer_opportunities' => true,
        ]);

        $project->budgetLines()->where('title', 'Inclusion Support')->firstOrFail()->update([
            'allocated_budget' => 1200,
        ]);

        $funding = $this->createSection($project, 'Have you identified the need of any specific additional funding? If this is the case, please fill in the table below.', '', 1000, 'Activities', 0, 'additional-funding-needs');
        $activities = $this->createSection($project, 'What activities do you plan to implement? What is the number and profile of the participants involved?', '', 1000, 'Project summary', 1, 'summary-activities');

        $this->actingAs($user);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Populate from project')
            ->call('autofillTable', $funding->id, 'additional_funding')
            ->call('autofillTable', $activities->id, 'activity_plan');

        $funding->refresh();
        $activities->refresh();

        $this->assertSame('Inclusion Support', $funding->application_tables['additional_funding'][0]['cost_type']);
        $this->assertSame('1 participants with fewer opportunities', $funding->application_tables['additional_funding'][0]['participants']);
        $this->assertSame('Youth Exchange', $activities->application_tables['activity_plan'][0]['activity']);
        $this->assertStringContainsString('Participant', $activities->application_tables['activity_plan'][0]['participants']);
    }

    public function test_application_can_be_exported_as_word_compatible_document(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $this->createSection($project, 'Official question', 'Clean answer for export.', 1000, 'Context', 0, 'official-question');

        $this->actingAs($user);

        $this->get(route('projects.export-application-word', $project))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/msword; charset=UTF-8')
            ->assertSee('Official question')
            ->assertSee('Clean answer for export.')
            ->assertDontSee('Writing guidance')
            ->assertDontSee('Evaluator hints');
    }

    public function test_activity_flows_can_be_generated_and_used_by_application_pack(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $project->update([
            'ka_action' => 'ka152-you',
            'mobility_start_date' => '2026-08-01',
            'mobility_end_date' => '2026-08-07',
            'partner_orgs' => [
                ['name' => 'Scoala de Jocuri', 'country' => 'Romania', 'oid' => null],
            ],
        ]);
        $this->createSection($project, 'What activities do you plan to implement? What is the number and profile of the participants involved?', '', 1000, 'Project summary', 0, 'summary-activities');
        $project->participants()->create([
            'first_name' => 'Ana',
            'last_name' => 'Pop',
            'country' => 'Spain',
            'partner_organisation' => 'Partner Spain',
            'role' => 'participant',
            'fewer_opportunities' => true,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->call('openActivityBuilder')
            ->assertSet('showActivityBuilder', true)
            ->call('generateActivityFlowsFromParticipants');

        $summary = $component->instance()->getActivityFlowSummary();
        $review = $component->instance()->getActivityFlowReview();

        $this->assertSame(1, $summary['count']);
        $this->assertSame(1, $summary['participants']);
        $storedFlow = $project->fresh()->action_data['application_flows'][0];
        $this->assertSame('Partner Spain', $storedFlow['group_label']);
        $this->assertSame('7', $storedFlow['duration_days']);
        $this->assertContains('Country balance may be incomplete', collect($review['issues'])->pluck('title')->all());

        $this->get(route('projects.export-application-pack', $project))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_activity_flow_review_flags_participant_mismatches_and_youth_exchange_age_range(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $project->update([
            'ka_action' => 'ka152-you',
            'mobility_start_date' => '2026-08-01',
            'mobility_end_date' => '2026-08-07',
            'action_data' => [
                'application_flows' => [[
                    'activity_id' => 'A1',
                    'flow_id' => 'F1',
                    'activity_type' => 'ka152-you',
                    'group_label' => 'Spain group',
                    'origin_country' => 'Spain',
                    'destination_country' => 'Romania',
                    'start_date' => '2026-08-01',
                    'end_date' => '2026-08-07',
                    'duration_days' => '7',
                    'travel_days' => '2',
                    'participants_count' => '1',
                    'fewer_opportunities_count' => '0',
                    'group_leaders_count' => '0',
                    'green_travel' => true,
                    'distance_band' => '',
                    'responsible' => 'Coordinator',
                    'learning_output' => 'Learning output',
                ]],
            ],
        ]);

        $project->participants()->create([
            'first_name' => 'Old',
            'last_name' => 'Participant',
            'birth_date' => '1990-01-01',
            'country' => 'Spain',
            'role' => 'participant',
            'fewer_opportunities' => true,
        ]);
        $project->participants()->create([
            'first_name' => 'Young',
            'last_name' => 'Participant',
            'birth_date' => '2010-01-01',
            'country' => 'Portugal',
            'role' => 'participant',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Flow quality');

        $review = $component->instance()->getActivityFlowReview();
        $titles = collect($review['issues'])->pluck('title')->all();

        $this->assertContains('Participant count mismatch', $titles);
        $this->assertContains('Fewer-opportunity count mismatch', $titles);
        $this->assertContains('Youth Exchange age range', $titles);
        $this->assertContains('F1: green travel without distance band', $titles);
        $this->assertGreaterThan(0, $review['critical']);
    }

    public function test_official_readiness_flags_conditional_missing_answers_and_navigation(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $project->update(['ka_action' => 'ka152-you']);
        $trigger = $this->createSection(
            $project,
            'Do you foresee Virtual/Blended activities and/or the use of any virtual component, before, during or after the activity?',
            'Yes, we will include virtual preparation.',
            1000,
            'Project summary',
            0,
            'virtual-blended-components',
        );
        $followUp = $this->createSection(
            $project,
            'Please describe the planned virtual or blended components.',
            '',
            1000,
            'Project summary',
            1,
            'virtual-blended-description',
        );

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Official readiness')
            ->assertSee('Next official issue')
            ->call('filterReviewStatus', 'official-issues')
            ->assertSet('sectionFilter', 'official-issues')
            ->call('focusNextOfficialIssue')
            ->assertSet('writingMode', 'focus')
            ->assertSet('focusSectionId', $followUp->id);

        $review = $component->instance()->getOfficialCompletenessReview();

        $this->assertGreaterThan(0, $review['critical']);
        $this->assertContains('Conditional official answer', collect($review['issues'])->pluck('area')->all());
        $this->assertSame([$followUp->id], $component->instance()->getVisibleSections()->pluck('id')->all());
        $this->assertNotSame($trigger->id, $component->instance()->focusSectionId);
    }

    public function test_submission_checklist_is_action_specific_for_ka152(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $project->update(['ka_action' => 'ka152-you']);
        $participantBackground = $this->createSection($project, 'Participant background', '', 4000, 'Description of the activity', 0, 'activity-participant-background');
        $this->createSection($project, 'European certificates', 'Yes, we will use Youthpass.', 1000, 'Project design', 1, 'european-certificates');
        $fewerMeasures = $this->createSection($project, 'Fewer opportunities measures', '', 4000, 'Participant with fewer opportunities', 2, 'fewer-opportunities-measures');
        $this->createSection($project, 'Safety and protection', '', 3000, 'Project design', 3, 'safety-protection');
        $this->createSection($project, 'Evaluation', '', 3500, 'Project management', 4, 'evaluation');
        $this->createSection($project, 'Dissemination', '', 3500, 'Project management', 5, 'dissemination');

        $project->participants()->create([
            'first_name' => 'Ana',
            'last_name' => 'Pop',
            'role' => 'participant',
            'fewer_opportunities' => true,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Submission checklist')
            ->call('openReviewDetails')
            ->assertSee('KA152 participant groups')
            ->assertSee('KA152 fewer-opportunities support')
            ->assertSee('KA152 safety and protection');

        $checklist = $component->instance()->getSubmissionChecklist();
        $itemsByLabel = collect($checklist['items'])->keyBy('label');

        $this->assertSame('missing', $itemsByLabel['KA152 participant groups']['status']);
        $this->assertSame($participantBackground->id, $itemsByLabel['KA152 participant groups']['sectionId']);
        $this->assertSame('missing', $itemsByLabel['KA152 fewer-opportunities support']['status']);
        $this->assertSame($fewerMeasures->id, $itemsByLabel['KA152 fewer-opportunities support']['sectionId']);
        $this->assertGreaterThan(0, $checklist['missing']);
        $this->assertLessThan(100, $checklist['score']);
    }

    public function test_answer_scaffold_can_be_inserted_for_a_question(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $section = $this->createSection($project, 'Expected impact and follow-up', '', 2000, 'Impact', 0, 'summary-impact');

        $this->actingAs($user);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->call('insertAnswerScaffold', $section->id);

        $this->assertStringContainsString('Expected result / proof', $section->fresh()->content);
    }

    public function test_template_switch_replaces_old_official_questions_and_preserves_custom_sections(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $oldOfficial = $this->createSection($project, 'Legacy impact question', 'This belongs to another form.', 1000, 'Old form', 0, 'summary-impact');
        $custom = $this->createSection($project, 'Internal evaluator note', 'Keep this project-specific note.', 1000, 'Custom', 1, 'custom-note');

        $this->actingAs($user);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->set('selectedTemplate', 'ka151-you')
            ->call('loadTemplate')
            ->assertSee('Writing guidance');

        $this->assertNull($oldOfficial->fresh());
        $this->assertSame('Keep this project-specific note.', $custom->fresh()->content);
        $this->assertSame('ka151-you', $project->fresh()->ka_action);
        $this->assertTrue($project->applicationSections()->where('question_key', 'virtual-blended-components')->exists());
        $this->assertFalse($project->applicationSections()->where('question_key', 'summary-impact')->exists());
        $this->assertSame(1, ProjectApplicationVersion::where('project_id', $project->id)->count());
    }

    public function test_consistency_checker_flags_missing_answers_and_erasmus_themes(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $this->createSection($project, 'Project objectives', 'We will improve youth participation.', 1000, 'Project rationale', 0, 'needs-objectives');
        $this->createSection($project, 'Expected impact', '', 1000, 'Project management', 1, 'summary-impact');

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Consistency checker')
            ->assertSee('View checks')
            ->assertSee('Inclusion');

        $review = $component->instance()->getConsistencyReview();

        $this->assertGreaterThan(0, $review['critical']);
        $this->assertGreaterThan(0, $review['warning']);
        $this->assertLessThan(100, $review['score']);
        $this->assertContains('Learning recognition', collect($review['issues'])->pluck('area')->all());
    }

    public function test_quality_review_scores_evaluator_criteria(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
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
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $this->createSection($project, 'Project objectives', 'We will improve youth participation.', 1000, 'Project rationale', 0, 'needs-objectives');
        $this->createSection($project, 'Expected impact', '', 1000, 'Project management', 1, 'summary-impact');

        $this->actingAs($user);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('View checks')
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

    public function test_application_modes_support_review_and_focus_workflows(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $first = $this->createSection($project, 'Objectives', 'Readable answer for partners.', 1000, 'Context', 0, 'objectives');
        $second = $this->createSection($project, 'Impact', 'Second answer.', 1000, 'Impact', 1, 'impact');

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Write')
            ->assertSee('Review')
            ->assertSee('Focus')
            ->call('setWritingMode', 'review')
            ->assertSet('writingMode', 'review')
            ->assertSee('Readable answer for partners.')
            ->call('enterFocusMode', $second->id)
            ->assertSet('writingMode', 'focus')
            ->assertSet('focusSectionId', $second->id)
            ->assertSee('Focus mode');

        $this->assertSame(
            [$second->id],
            $component->instance()->getVisibleSections()->pluck('id')->all(),
        );

        $component
            ->call('moveFocus', -1)
            ->assertSet('focusSectionId', $first->id);

        $this->assertSame(
            [$first->id],
            $component->instance()->getVisibleSections()->pluck('id')->all(),
        );
    }

    public function test_review_queue_filters_and_updates_section_statuses(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $draft = $this->createSection($project, 'Draft answer', 'Still writing.', 1000, 'Context', 0);
        $review = $this->createSection($project, 'Reviewer answer', 'Needs another look.', 1000, 'Context', 1);
        $ready = $this->createSection($project, 'Ready answer', 'Final enough.', 1000, 'Impact', 2);

        $review->update(['review_status' => 'review', 'internal_notes' => 'Check evidence.']);
        $ready->update(['review_status' => 'ready']);

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Review queue')
            ->assertSee('with notes')
            ->call('filterReviewStatus', 'review')
            ->assertSet('writingMode', 'review')
            ->assertSet('sectionFilter', 'review');

        $this->assertSame(
            [$review->id],
            $component->instance()->getVisibleSections()->pluck('id')->all(),
        );

        $component
            ->call('setReviewStatus', $draft->id, 'ready')
            ->assertSet("reviewStatuses.{$draft->id}", 'ready');

        $this->assertSame('ready', $draft->fresh()->review_status);
        $this->assertSame(0, $component->instance()->getApplicationSummary()['draft']);
        $this->assertSame(2, $component->instance()->getApplicationSummary()['ready']);
    }

    public function test_bulk_review_actions_flag_issues_generate_notes_and_mark_answered_ready(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $answered = $this->createSection($project, 'Project objectives', 'A clear draft answer.', 1000, 'Context', 0, 'needs-objectives');
        $empty = $this->createSection($project, 'Expected impact', '', 1000, 'Impact', 1, 'summary-impact');

        $this->actingAs($user);

        $component = Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Flag')
            ->assertSee('Generate notes')
            ->assertSee('Mark')
            ->call('sendIssueSectionsToReview')
            ->assertSet("reviewStatuses.{$empty->id}", 'review')
            ->assertSee('with notes');

        $this->assertSame('review', $empty->fresh()->review_status);
        $this->assertStringContainsString('Unanswered questions', $empty->fresh()->internal_notes);

        $component
            ->call('markAnsweredSectionsReady')
            ->assertSet("reviewStatuses.{$answered->id}", 'ready');

        $this->assertSame('ready', $answered->fresh()->review_status);
        $this->assertSame('review', $empty->fresh()->review_status);

        $component->call('generateReviewNotesFromChecks');

        $this->assertStringContainsString('Unanswered questions', $empty->fresh()->internal_notes);
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $user = User::factory()->create();
        $owner = $role === 'viewer' ? User::factory()->create() : $user;
        $project = Project::create([
            'owner_id' => $owner->id,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'ka_action' => 'ka152',
        ]);

        if ($role === 'viewer') {
            $project->members()->attach($user, ['role' => Project::PROJECT_ROLE_VIEWER]);
        }

        return [$project, $user];
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
