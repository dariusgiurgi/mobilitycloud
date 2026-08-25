<?php

namespace Tests\Feature;

use App\Filament\Pages\FeedbackForms;
use App\Filament\Resources\Projects\Pages\ViewProjectFeedback;
use App\Filament\Resources\Projects\Pages\ViewProjectMobility;
use App\Models\FeedbackForm;
use App\Models\MobilityFeedbackCampaign;
use App\Models\MobilityFeedbackResponse;
use App\Models\Project;
use App\Models\User;
use App\Services\MobilityFeedbackAnalytics;
use App\Services\ProjectFinalArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class MobilityFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feedback_form_records_answers_without_participant_identity(): void
    {
        [$user, $project, $mobility] = $this->projectMobility();
        $campaign = $this->campaign($user, $mobility);

        $this->get(route('public.mobility-feedback.show', $campaign->public_token))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('<meta name="referrer" content="no-referrer">', false)
            ->assertSee('<meta name="robots" content="noindex,nofollow,noarchive">', false)
            ->assertSee('Anonymous mobility feedback')
            ->assertSee('How satisfied were you?')
            ->assertDontSee($user->name);

        $this->post(route('public.mobility-feedback.store', $campaign->public_token), [
            'answers' => [
                'q_rating' => 5,
                'q_comment' => 'The activities were engaging and well organised.',
            ],
        ])->assertRedirect(route('public.mobility-feedback.show', $campaign->public_token));

        $response = MobilityFeedbackResponse::query()->sole();
        $this->assertSame($campaign->id, $response->mobility_feedback_campaign_id);
        $this->assertSame([
            'q_rating' => 5,
            'q_comment' => 'The activities were engaging and well organised.',
        ], $response->answers);
        $this->assertFalse(Schema::hasColumn('mobility_feedback_responses', 'user_id'));
        $this->assertFalse(Schema::hasColumn('mobility_feedback_responses', 'participant_id'));
    }

    public function test_closed_feedback_link_cannot_record_more_answers(): void
    {
        [$user, , $mobility] = $this->projectMobility();
        $campaign = $this->campaign($user, $mobility);
        $campaign->update(['closed_at' => now()]);

        $this->get(route('public.mobility-feedback.show', $campaign->public_token))
            ->assertOk()
            ->assertSee('currently closed');

        $this->post(route('public.mobility-feedback.store', $campaign->public_token), [
            'answers' => [
                'q_rating' => 5,
                'q_comment' => 'This must not be saved.',
            ],
        ])->assertNotFound();

        $this->assertDatabaseCount('mobility_feedback_responses', 0);
    }

    public function test_malformed_feedback_tokens_are_rejected_by_the_router(): void
    {
        $this->get('/mobility-feedback/'.str_repeat('a', 63))->assertNotFound();
        $this->post('/mobility-feedback/'.str_repeat('a', 65))->assertNotFound();
        $this->get('/mobility-feedback/'.str_repeat('-', 64))->assertNotFound();
    }

    public function test_public_feedback_form_requires_the_configured_answers(): void
    {
        [$user, , $mobility] = $this->projectMobility();
        $campaign = $this->campaign($user, $mobility);

        $this->from(route('public.mobility-feedback.show', $campaign->public_token))
            ->post(route('public.mobility-feedback.store', $campaign->public_token), ['answers' => []])
            ->assertRedirect(route('public.mobility-feedback.show', $campaign->public_token))
            ->assertSessionHasErrors(['answers.q_rating', 'answers.q_comment']);

        $this->assertSame(0, MobilityFeedbackResponse::query()->count());
    }

    public function test_feedback_export_is_available_to_project_members_only(): void
    {
        [$user, , $mobility] = $this->projectMobility();
        $campaign = $this->campaign($user, $mobility);
        MobilityFeedbackResponse::create([
            'mobility_feedback_campaign_id' => $campaign->id,
            'answers' => ['q_rating' => 4, 'q_comment' => 'Useful and inclusive.'],
            'submitted_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('feedback-campaigns.export', $campaign))
            ->assertForbidden();

        $response = $this->actingAs($user)
            ->get(route('feedback-campaigns.export', $campaign))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('How satisfied were you?', $csv);
        $this->assertStringContainsString('Useful and inclusive.', $csv);
    }

    public function test_feedback_results_can_be_exported_as_a_pdf_without_participant_identity(): void
    {
        [$user, , $mobility] = $this->projectMobility();
        $campaign = $this->campaign($user, $mobility);
        MobilityFeedbackResponse::create([
            'mobility_feedback_campaign_id' => $campaign->id,
            'answers' => ['q_rating' => 4, 'q_comment' => 'Well structured and useful.'],
            'submitted_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('feedback-campaigns.export-pdf', $campaign))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('feedback-campaigns.export-pdf', $campaign))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('anonymous-feedback-porto-final-feedback.pdf');
    }

    public function test_owner_can_build_a_global_form_and_share_a_snapshot_from_the_project_feedback_module(): void
    {
        [$user, $project, $mobility] = $this->projectMobility();
        $this->actingAs($user);

        Livewire::test(FeedbackForms::class)
            ->call('openCreateForm', true)
            ->assertSee('Final mobility evaluation')
            ->call('saveForm')
            ->assertHasNoErrors();

        $form = FeedbackForm::query()->sole();
        $this->assertCount(5, $form->questions);

        Livewire::test(ViewProjectFeedback::class, ['record' => $project->id])
            ->call('openShareForm', $form->id)
            ->set('shareMobilityId', (string) $mobility->id)
            ->set('shareCampaignTitle', 'Porto evaluation')
            ->call('createCampaign')
            ->assertHasNoErrors()
            ->assertSee('Porto evaluation');

        $campaign = MobilityFeedbackCampaign::query()->sole();
        $this->assertSame($form->questions, data_get($campaign->form_snapshot, 'questions'));
        $this->assertTrue($campaign->hasActiveLink());
    }

    public function test_feedback_forms_page_is_available_in_the_global_menu(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertTrue(FeedbackForms::canAccess());
        $this->get(FeedbackForms::getUrl())
            ->assertOk()
            ->assertSee('Feedback forms')
            ->assertSee('Use evaluation starter');
    }

    public function test_custom_choice_questions_are_saved_as_clean_options_for_the_public_form(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(FeedbackForms::class)
            ->call('openCreateForm')
            ->call('addQuestion', 'single_choice')
            ->set('formName', 'Custom choices')
            ->set('formQuestions.0.label', 'Would you recommend this mobility?')
            ->set('formQuestions.0.options_text', "Yes\nNo")
            ->call('saveForm')
            ->assertHasNoErrors();

        $form = FeedbackForm::query()->sole();
        $this->assertSame(['Yes', 'No'], $form->questions[0]['options']);
        $this->assertArrayNotHasKey('options_text', $form->questions[0]);
    }

    public function test_feedback_results_are_grouped_by_question_with_distributions_and_comments(): void
    {
        [$user, , $mobility] = $this->projectMobility();
        $campaign = $this->campaign($user, $mobility);
        $campaign->update(['form_snapshot' => [
            'questions' => [
                ['id' => 'q_rating', 'type' => 'rating', 'label' => 'How satisfied were you?', 'options' => []],
                ['id' => 'q_choice', 'type' => 'single_choice', 'label' => 'Would you recommend it?', 'options' => ['Yes', 'No']],
                ['id' => 'q_comment', 'type' => 'long_text', 'label' => 'What worked well?', 'options' => []],
            ],
        ]]);
        MobilityFeedbackResponse::insert([
            ['mobility_feedback_campaign_id' => $campaign->id, 'answers' => json_encode(['q_rating' => 5, 'q_choice' => 'Yes', 'q_comment' => 'Very welcoming team.']), 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['mobility_feedback_campaign_id' => $campaign->id, 'answers' => json_encode(['q_rating' => 3, 'q_choice' => 'No', 'q_comment' => 'More free time would help.']), 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $report = app(MobilityFeedbackAnalytics::class)->forCampaign($campaign->fresh());

        $this->assertSame(2, $report['response_count']);
        $this->assertSame(4.0, $report['overall_rating']);
        $this->assertCount(3, $report['questions']);
        $this->assertEquals(50, $report['questions'][1]['options'][0]['percent']);
        $this->assertEqualsCanonicalizing(['Very welcoming team.', 'More free time would help.'], $report['questions'][2]['answers']);
    }

    public function test_feedback_results_are_available_from_the_project_feedback_module(): void
    {
        [$user, $project, $mobility] = $this->projectMobility();
        $campaign = $this->campaign($user, $mobility);
        MobilityFeedbackResponse::create([
            'mobility_feedback_campaign_id' => $campaign->id,
            'answers' => ['q_rating' => 5, 'q_comment' => 'Excellent group experience.'],
            'submitted_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(ViewProjectFeedback::class, ['record' => $project->id])
            ->assertSee('Feedback links in this project')
            ->assertSee('Porto final feedback')
            ->call('openResults', $campaign->id)
            ->assertSet('showResultsModal', true)
            ->assertSee('Excellent group experience.');
    }

    public function test_mobility_workspace_does_not_show_feedback_controls(): void
    {
        [$user, $project, $mobility] = $this->projectMobility();
        $this->campaign($user, $mobility);
        $this->actingAs($user);

        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->assertDontSee('Participant feedback')
            ->assertDontSee('View results');
    }

    public function test_project_editor_and_mobility_coordinator_can_manage_project_feedback_with_owner_forms(): void
    {
        [$owner, $project, $mobility] = $this->projectMobility();
        $form = FeedbackForm::create([
            'owner_id' => $owner->id,
            'name' => 'Owner evaluation form',
            'questions' => [['id' => 'q_rating', 'type' => 'rating', 'label' => 'Rate it', 'required' => true, 'options' => []]],
        ]);

        foreach ([Project::PROJECT_ROLE_EDITOR, Project::PROJECT_ROLE_MOBILITY] as $role) {
            $collaborator = User::factory()->create();
            $project->members()->attach($collaborator, ['role' => $role]);
            $this->actingAs($collaborator);

            Livewire::test(ViewProjectFeedback::class, ['record' => $project->id])
                ->assertSee('Owner evaluation form')
                ->call('openShareForm', $form->id)
                ->set('shareMobilityId', (string) $mobility->id)
                ->set('shareCampaignTitle', $role.' feedback')
                ->call('createCampaign')
                ->assertHasNoErrors();
        }

        $this->assertSame(2, MobilityFeedbackCampaign::query()->count());
    }

    public function test_final_archive_includes_a_feedback_pdf_for_each_mobility_campaign(): void
    {
        [$user, $project, $mobility] = $this->projectMobility();
        $campaign = $this->campaign($user, $mobility);
        MobilityFeedbackResponse::create([
            'mobility_feedback_campaign_id' => $campaign->id,
            'answers' => ['q_rating' => 5, 'q_comment' => 'Great experience.'],
            'submitted_at' => now(),
        ]);

        $archive = app(ProjectFinalArchiveService::class)->create($project);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($archive) === true);
        $path = 'learning-together/10-participant-feedback/mobility-in-porto-'.$campaign->id.'/anonymous-feedback-porto-final-feedback.pdf';
        $this->assertNotFalse($zip->locateName($path));
        $this->assertStringStartsWith('%PDF-', $zip->getFromName($path));
        $payload = json_decode($zip->getFromName('learning-together/00-project-data/project-data.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Porto final feedback', $payload['feedback_campaigns'][0]['title']);
        $zip->close();
        unlink($archive);
    }

    public function test_final_archive_can_exclude_feedback_reports_when_the_category_is_unselected(): void
    {
        [$user, $project, $mobility] = $this->projectMobility();
        $campaign = $this->campaign($user, $mobility);
        $project->update(['action_data' => [
            'finalisation' => ['include' => array_merge(array_fill_keys([
                'project_data', 'application', 'participants', 'budget', 'agreements', 'generated_records', 'project_files', 'mobility', 'dissemination',
            ], true), ['feedback' => false])],
        ]]);

        $archive = app(ProjectFinalArchiveService::class)->create($project);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($archive) === true);
        $path = 'learning-together/10-participant-feedback/mobility-in-porto-'.$campaign->id.'/anonymous-feedback-porto-final-feedback.pdf';
        $this->assertFalse($zip->locateName($path));
        $zip->close();
        unlink($archive);
    }

    private function projectMobility(): array
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Learning Together',
            'acronym' => 'LT',
            'status' => 'active',
        ]);
        $mobility = $project->mobilities()->create([
            'name' => 'Mobility in Porto',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-16',
        ]);

        return [$user, $project, $mobility];
    }

    private function campaign(User $user, $mobility): MobilityFeedbackCampaign
    {
        $form = FeedbackForm::create([
            'owner_id' => $user->id,
            'name' => 'Final mobility evaluation',
            'questions' => [
                ['id' => 'q_rating', 'type' => 'rating', 'label' => 'How satisfied were you?', 'required' => true, 'options' => []],
                ['id' => 'q_comment', 'type' => 'long_text', 'label' => 'What worked well?', 'required' => true, 'options' => []],
            ],
        ]);

        return MobilityFeedbackCampaign::create([
            'project_mobility_id' => $mobility->id,
            'feedback_form_id' => $form->id,
            'created_by' => $user->id,
            'title' => 'Porto final feedback',
            'public_token' => str_repeat('a', 64),
            'opened_at' => now(),
            'form_snapshot' => [
                'form_name' => $form->name,
                'intro_text' => 'Share your feedback anonymously.',
                'questions' => $form->questions,
            ],
        ]);
    }
}
