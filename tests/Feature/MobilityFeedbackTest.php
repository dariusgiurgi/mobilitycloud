<?php

namespace Tests\Feature;

use App\Filament\Pages\FeedbackForms;
use App\Models\FeedbackForm;
use App\Models\MobilityFeedbackCampaign;
use App\Models\MobilityFeedbackResponse;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MobilityFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feedback_form_records_answers_without_participant_identity(): void
    {
        [$user, $project, $mobility] = $this->projectMobility();
        $campaign = $this->campaign($user, $mobility);

        $this->get(route('public.mobility-feedback.show', $campaign->public_token))
            ->assertOk()
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

    public function test_owner_can_build_a_global_form_and_share_a_snapshot_with_a_mobility(): void
    {
        [$user, , $mobility] = $this->projectMobility();
        $this->actingAs($user);

        Livewire::test(FeedbackForms::class)
            ->call('openCreateForm', true)
            ->assertSee('Final mobility evaluation')
            ->call('saveForm')
            ->assertHasNoErrors();

        $form = FeedbackForm::query()->sole();
        $this->assertCount(5, $form->questions);

        Livewire::test(FeedbackForms::class)
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
