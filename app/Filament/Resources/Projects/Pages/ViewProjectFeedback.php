<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Pages\FeedbackForms;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\FeedbackForm;
use App\Models\MobilityFeedbackCampaign;
use App\Services\MobilityFeedbackAnalytics;
use App\Support\AuthorizesProjectManagement;
use App\Support\PlatformAccess;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Str;

class ViewProjectFeedback extends Page
{
    use AuthorizesProjectManagement;
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.view-project-feedback';

    public bool $showShareModal = false;

    public bool $showResultsModal = false;

    public ?int $shareFormId = null;

    public ?int $viewingCampaignId = null;

    public string $shareMobilityId = '';

    public string $shareCampaignTitle = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        ProjectResource::ensureProjectAccountTenant($this->record, 'feedback');
        $this->authorizeProjectModuleAccess('feedback');
        $this->touchProjectCollaboration('feedback');
    }

    public function getTitle(): string
    {
        return $this->record->name.' — Feedback';
    }

    public function getForms()
    {
        return FeedbackForm::query()
            ->where('owner_id', $this->record->owner_id)
            ->where('is_archived', false)
            ->withCount('campaigns')
            ->latest('updated_at')
            ->get();
    }

    public function getMobilities()
    {
        return $this->record->mobilities()->get();
    }

    public function getCampaigns()
    {
        return MobilityFeedbackCampaign::query()
            ->with('mobility')
            ->withCount('responses')
            ->whereHas('mobility', fn ($query) => $query->where('project_id', $this->record->id))
            ->latest('id')
            ->get();
    }

    public function getViewingCampaign(): ?MobilityFeedbackCampaign
    {
        if (! $this->viewingCampaignId) {
            return null;
        }

        $campaign = MobilityFeedbackCampaign::query()
            ->with(['mobility.project', 'responses' => fn ($query) => $query->latest('submitted_at')])
            ->find($this->viewingCampaignId);

        return $campaign
            && (int) $campaign->mobility?->project_id === (int) $this->record->id
            && $campaign->canBeAccessedBy(auth()->user())
            ? $campaign
            : null;
    }

    public function getViewingCampaignAnalytics(): array
    {
        $campaign = $this->getViewingCampaign();

        return $campaign
            ? app(MobilityFeedbackAnalytics::class)->forCampaign($campaign)
            : ['response_count' => 0, 'question_count' => 0, 'overall_rating' => null, 'questions' => []];
    }

    public function openShareForm(int $formId): void
    {
        $this->authorizeFeedbackManagement();

        $form = $this->projectForm($formId);
        $this->shareFormId = $form->id;
        $this->shareCampaignTitle = $form->name;
        $this->shareMobilityId = (string) ($this->getMobilities()->first()?->id ?: '');
        $this->showShareModal = true;
    }

    public function createCampaign(): void
    {
        $this->authorizeFeedbackManagement();
        $data = $this->validate([
            'shareMobilityId' => ['required', 'integer'],
            'shareCampaignTitle' => ['required', 'string', 'max:160'],
        ]);
        $form = $this->projectForm((int) $this->shareFormId);
        $mobility = $this->record->mobilities()->find((int) $data['shareMobilityId']);
        abort_unless($mobility, 403);

        $campaign = MobilityFeedbackCampaign::create([
            'project_mobility_id' => $mobility->id,
            'feedback_form_id' => $form->id,
            'created_by' => auth()->id(),
            'title' => trim($data['shareCampaignTitle']),
            'public_token' => Str::random(64),
            'form_snapshot' => [
                'form_name' => $form->name,
                'description' => $form->description,
                'intro_text' => $form->intro_text,
                'thank_you_text' => $form->thank_you_text,
                'questions' => $form->questions,
                'version_created_at' => now()->toIso8601String(),
            ],
            'opened_at' => now(),
        ]);

        $this->showShareModal = false;
        $this->viewingCampaignId = $campaign->id;
        $this->showResultsModal = true;
        Notification::make()->title('Anonymous feedback link created')->body('Share the link with the participants of '.$mobility->name.'.')->success()->send();
    }

    public function openResults(int $campaignId): void
    {
        $campaign = $this->campaign($campaignId);
        abort_unless($campaign->canBeAccessedBy(auth()->user()), 403);

        $this->viewingCampaignId = $campaign->id;
        $this->showResultsModal = true;
    }

    public function closeCampaign(int $campaignId): void
    {
        $this->authorizeFeedbackManagement();
        $this->campaign($campaignId)->update(['closed_at' => now()]);
        Notification::make()->title('Feedback link closed')->success()->send();
    }

    public function reopenCampaign(int $campaignId): void
    {
        $this->authorizeFeedbackManagement();
        $this->campaign($campaignId)->update(['opened_at' => now(), 'closed_at' => null]);
        Notification::make()->title('Feedback link reopened')->success()->send();
    }

    public function closeModal(string $modal): void
    {
        if ($modal === 'share') {
            $this->showShareModal = false;
            $this->shareFormId = null;
        }

        if ($modal === 'results') {
            $this->showResultsModal = false;
            $this->viewingCampaignId = null;
        }
    }

    public function canManageFeedback(): bool
    {
        return ! PlatformAccess::isReadOnly() && $this->record->canManageProjectModule(auth()->user(), 'feedback');
    }

    public function formLibraryUrl(): string
    {
        return FeedbackForms::getUrl();
    }

    public function canManageFormLibrary(): bool
    {
        return $this->record->isOwnedBy(auth()->user());
    }

    private function authorizeFeedbackManagement(): void
    {
        abort_unless($this->canManageFeedback(), 403);
    }

    private function projectForm(int $formId): FeedbackForm
    {
        return FeedbackForm::query()
            ->where('owner_id', $this->record->owner_id)
            ->where('is_archived', false)
            ->findOrFail($formId);
    }

    private function campaign(int $campaignId): MobilityFeedbackCampaign
    {
        $campaign = MobilityFeedbackCampaign::query()->with('mobility.project')->findOrFail($campaignId);
        abort_unless((int) $campaign->mobility?->project_id === (int) $this->record->id, 403);

        return $campaign;
    }
}
