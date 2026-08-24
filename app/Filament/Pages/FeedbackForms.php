<?php

namespace App\Filament\Pages;

use App\Models\FeedbackForm;
use App\Models\MobilityFeedbackCampaign;
use App\Models\ProjectMobility;
use App\Services\MobilityFeedbackAnalytics;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FeedbackForms extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Feedback forms';

    protected static string|\UnitEnum|null $navigationGroup = 'Planning tools';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Feedback forms';

    protected string $view = 'filament.pages.feedback-forms';

    public ?int $editingFormId = null;

    public bool $showFormEditor = false;

    public bool $showShareModal = false;

    public bool $showResultsModal = false;

    public ?int $sharingFormId = null;

    public ?int $viewingCampaignId = null;

    public string $formName = '';

    public string $formDescription = '';

    public string $formIntro = '';

    public string $formThankYou = 'Thank you for sharing your feedback. Your response was recorded anonymously.';

    /** @var array<int, array<string, mixed>> */
    public array $formQuestions = [];

    public string $shareMobilityId = '';

    public string $shareCampaignTitle = '';

    public static function canAccess(): bool
    {
        return auth()->check() && PlatformAccess::canUse(PlanCatalog::MODULE_PROJECTS);
    }

    public function getSubheading(): ?string
    {
        return 'Build reusable surveys once. Create links and review results from the Feedback module inside each project.';
    }

    public function getFormsProperty(): Collection
    {
        return FeedbackForm::query()
            ->ownedBy(auth()->user())
            ->where('is_archived', false)
            ->withCount('campaigns')
            ->latest('updated_at')
            ->get();
    }

    public function getCampaignsProperty(): Collection
    {
        return MobilityFeedbackCampaign::query()
            ->with(['mobility.project'])
            ->withCount('responses')
            ->whereHas('mobility.project', fn ($query) => $query->visibleToAccount(auth()->user()))
            ->latest('id')
            ->get()
            ->filter(fn (MobilityFeedbackCampaign $campaign): bool => $campaign->canBeAccessedBy(auth()->user()))
            ->values();
    }

    public function getAvailableMobilitiesProperty(): Collection
    {
        return ProjectMobility::query()
            ->with('project')
            ->whereHas('project', fn ($query) => $query->visibleToAccount(auth()->user()))
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (ProjectMobility $mobility): bool => $mobility->project?->canManageProjectModule(auth()->user(), 'mobility') ?? false)
            ->values();
    }

    public function getViewingCampaignProperty(): ?MobilityFeedbackCampaign
    {
        if (! $this->viewingCampaignId) {
            return null;
        }

        $campaign = MobilityFeedbackCampaign::query()
            ->with(['mobility.project', 'responses' => fn ($query) => $query->latest('submitted_at')])
            ->find($this->viewingCampaignId);

        return $campaign?->canBeAccessedBy(auth()->user()) ? $campaign : null;
    }

    public function getCampaignResultsProperty(): array
    {
        $campaign = $this->getViewingCampaignProperty();
        return $campaign
            ? app(MobilityFeedbackAnalytics::class)->forCampaign($campaign)
            : ['response_count' => 0, 'question_count' => 0, 'overall_rating' => null, 'questions' => []];
    }

    public function openCreateForm(bool $starter = false): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        $this->resetFormEditor();
        $this->showFormEditor = true;

        if ($starter) {
            $this->formName = 'Final mobility evaluation';
            $this->formDescription = 'Anonymous participant feedback after a mobility.';
            $this->formIntro = 'Your answers are anonymous. Please answer honestly; no name, email or participant record is collected.';
            $this->formQuestions = [
                $this->question('rating', 'How satisfied were you with the mobility overall?'),
                $this->question('rating', 'How would you rate the activities and learning experience?'),
                $this->question('rating', 'How would you rate the organisation and practical arrangements?'),
                $this->question('long_text', 'What was the most valuable part of the experience?', false),
                $this->question('long_text', 'What could be improved next time?', false),
            ];
        }
    }

    public function editForm(int $formId): void
    {
        $form = $this->ownedForm($formId);
        $this->editingFormId = $form->id;
        $this->formName = $form->name;
        $this->formDescription = $form->description ?: '';
        $this->formIntro = $form->intro_text ?: '';
        $this->formThankYou = $form->thank_you_text ?: '';
        $this->formQuestions = collect($form->questions ?: [])
            ->map(function (array $question): array {
                $question['options_text'] = implode("\n", $question['options'] ?? []);

                return $question;
            })
            ->all();
        $this->showFormEditor = true;
    }

    public function addQuestion(string $type = 'rating'): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        abort_unless(array_key_exists($type, FeedbackForm::QUESTION_TYPES), 422);
        $this->formQuestions[] = $this->question($type);
    }

    public function removeQuestion(int $index): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        unset($this->formQuestions[$index]);
        $this->formQuestions = array_values($this->formQuestions);
    }

    public function moveQuestion(int $index, string $direction): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if (! isset($this->formQuestions[$index], $this->formQuestions[$target])) {
            return;
        }

        [$this->formQuestions[$index], $this->formQuestions[$target]] = [$this->formQuestions[$target], $this->formQuestions[$index]];
    }

    public function saveForm(): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        $data = $this->validate([
            'formName' => ['required', 'string', 'max:120'],
            'formDescription' => ['nullable', 'string', 'max:500'],
            'formIntro' => ['nullable', 'string', 'max:2000'],
            'formThankYou' => ['nullable', 'string', 'max:1000'],
            'formQuestions' => ['required', 'array', 'min:1', 'max:50'],
            'formQuestions.*.id' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'formQuestions.*.type' => ['required', Rule::in(array_keys(FeedbackForm::QUESTION_TYPES))],
            'formQuestions.*.label' => ['required', 'string', 'max:500'],
            'formQuestions.*.help' => ['nullable', 'string', 'max:500'],
            'formQuestions.*.required' => ['boolean'],
            'formQuestions.*.options_text' => ['nullable', 'string', 'max:3000'],
        ]);

        $questions = collect($data['formQuestions'])
            ->map(function (array $question, int $index): array {
                $type = $question['type'];
                $options = collect(preg_split('/\r\n|\r|\n/', (string) ($question['options_text'] ?? '')) ?: [])
                    ->map(fn (string $option) => trim($option))
                    ->filter()
                    ->values()
                    ->all();

                if (in_array($type, ['single_choice', 'multiple_choice'], true) && count($options) < 2) {
                    $this->addError('formQuestions.'.$index.'.options', 'Add at least two choices.');
                }

                return [
                    'id' => $question['id'],
                    'type' => $type,
                    'label' => trim($question['label']),
                    'help' => filled($question['help'] ?? null) ? trim($question['help']) : null,
                    'required' => (bool) ($question['required'] ?? false),
                    'options' => $options,
                ];
            })
            ->all();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $form = $this->editingFormId ? $this->ownedForm($this->editingFormId) : new FeedbackForm(['owner_id' => auth()->id()]);
        $form->fill([
            'name' => trim($data['formName']),
            'description' => filled($data['formDescription']) ? trim($data['formDescription']) : null,
            'intro_text' => filled($data['formIntro']) ? trim($data['formIntro']) : null,
            'thank_you_text' => filled($data['formThankYou']) ? trim($data['formThankYou']) : null,
            'questions' => $questions,
            'is_archived' => false,
        ])->save();

        $this->showFormEditor = false;
        $this->resetFormEditor();
        Notification::make()->title('Feedback form saved')->body('You can now share it with any mobility.')->success()->send();
    }

    public function duplicateForm(int $formId): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        $source = $this->ownedForm($formId);
        FeedbackForm::create([
            'owner_id' => auth()->id(),
            'name' => $source->name.' (copy)',
            'description' => $source->description,
            'intro_text' => $source->intro_text,
            'thank_you_text' => $source->thank_you_text,
            'questions' => collect($source->questions)->map(function (array $question): array {
                $question['id'] = $this->questionId();

                return $question;
            })->all(),
        ]);
        Notification::make()->title('Feedback form duplicated')->success()->send();
    }

    public function archiveForm(int $formId): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        $this->ownedForm($formId)->update(['is_archived' => true]);
        Notification::make()->title('Feedback form archived')->success()->send();
    }

    public function openShareForm(int $formId): void
    {
        $form = $this->ownedForm($formId);
        $this->sharingFormId = $form->id;
        $this->shareCampaignTitle = $form->name;
        $this->shareMobilityId = (string) ($this->availableMobilities->first()?->id ?: '');
        $this->showShareModal = true;
    }

    public function createCampaign(): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        $data = $this->validate([
            'shareMobilityId' => ['required', 'integer'],
            'shareCampaignTitle' => ['required', 'string', 'max:160'],
        ]);
        $form = $this->ownedForm((int) $this->sharingFormId);
        $mobility = $this->availableMobilities->firstWhere('id', (int) $data['shareMobilityId']);
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

    public function closeCampaign(int $campaignId): void
    {
        $campaign = $this->managedCampaign($campaignId);
        $campaign->update(['closed_at' => now()]);
        Notification::make()->title('Feedback link closed')->success()->send();
    }

    public function reopenCampaign(int $campaignId): void
    {
        $campaign = $this->managedCampaign($campaignId);
        $campaign->update(['opened_at' => now(), 'closed_at' => null]);
        Notification::make()->title('Feedback link reopened')->success()->send();
    }

    public function openResults(int $campaignId): void
    {
        $campaign = MobilityFeedbackCampaign::query()->with('mobility.project')->findOrFail($campaignId);
        abort_unless($campaign->canBeAccessedBy(auth()->user()), 403);
        $this->viewingCampaignId = $campaign->id;
        $this->showResultsModal = true;
    }

    public function closeModal(string $modal): void
    {
        if ($modal === 'editor') {
            $this->showFormEditor = false;
            $this->resetFormEditor();
        }
        if ($modal === 'share') {
            $this->showShareModal = false;
        }
        if ($modal === 'results') {
            $this->showResultsModal = false;
            $this->viewingCampaignId = null;
        }
    }

    private function ownedForm(int $formId): FeedbackForm
    {
        return FeedbackForm::query()->ownedBy(auth()->user())->where('is_archived', false)->findOrFail($formId);
    }

    private function managedCampaign(int $campaignId): MobilityFeedbackCampaign
    {
        $campaign = MobilityFeedbackCampaign::query()->with('mobility.project')->findOrFail($campaignId);
        abort_unless($campaign->canBeManagedBy(auth()->user()), 403);

        return $campaign;
    }

    /** @return array<string, mixed> */
    private function question(string $type, string $label = '', bool $required = true): array
    {
        return [
            'id' => $this->questionId(),
            'type' => $type,
            'label' => $label,
            'help' => null,
            'required' => $required,
            'options' => [],
            'options_text' => in_array($type, ['single_choice', 'multiple_choice'], true) ? "Option 1\nOption 2" : '',
        ];
    }

    private function questionId(): string
    {
        return 'q_'.str_replace('-', '', (string) Str::uuid());
    }

    private function resetFormEditor(): void
    {
        $this->resetValidation();
        $this->editingFormId = null;
        $this->formName = '';
        $this->formDescription = '';
        $this->formIntro = '';
        $this->formThankYou = 'Thank you for sharing your feedback. Your response was recorded anonymously.';
        $this->formQuestions = [];
    }
}
