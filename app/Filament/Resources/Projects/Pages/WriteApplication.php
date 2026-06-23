<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ContentBlock;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectApplicationVersion;
use App\Support\ApplicationTemplates;
use App\Support\AuthorizesProjectManagement;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WriteApplication extends Page
{
    use AuthorizesProjectManagement;
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.write-application';

    public string $selectedTemplate = 'ka152-you';

    /** @var array<int, string> sectionId => content (bound to the textareas) */
    public array $content = [];

    /** @var array<int, string> sectionId => title (bound to the title inputs) */
    public array $titles = [];

    /** @var array<int, string> */
    public array $reviewStatuses = [];

    /** @var array<int, string> */
    public array $internalNotes = [];

    public string $sectionSearch = '';

    public string $sectionFilter = 'all';

    public string $writingMode = 'edit';

    public ?int $focusSectionId = null;

    public bool $showTemplateDetails = false;

    public bool $showVersions = false;

    public bool $showReviewDetails = false;

    public string $versionLabel = '';

    public ?string $lastSavedAt = null;

    // ─── Library picker state ───
    public bool $showLibrary = false;

    public ?int $libraryTargetId = null;

    public string $librarySearch = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->selectedTemplate = ApplicationTemplates::normaliseKey($this->record->ka_action ?: 'ka152-you');
        if (! ApplicationTemplates::get($this->selectedTemplate)) {
            $this->selectedTemplate = 'ka152-you';
        }
        $this->loadState();
    }

    public function getTitle(): string
    {
        return $this->record->name.' — Application';
    }

    protected function sectionsQuery()
    {
        return ProjectApplicationSection::where('project_id', $this->record->id)
            ->orderBy('sort_order')->orderBy('id');
    }

    public function getSections()
    {
        return $this->sectionsQuery()->get();
    }

    public function getVisibleSections()
    {
        $sections = $this->getSections()->filter(function (ProjectApplicationSection $section) {
            $text = trim(strip_tags($this->content[$section->id] ?? (string) $section->content));
            $matchesSearch = $this->sectionSearch === '' || str_contains(
                mb_strtolower($section->title.' '.$section->category.' '.$text),
                mb_strtolower($this->sectionSearch)
            );

            $matchesFilter = match ($this->sectionFilter) {
                'empty' => $text === '',
                'over-limit' => $section->char_limit && mb_strlen($text) > $section->char_limit,
                'review' => ($this->reviewStatuses[$section->id] ?? $section->review_status) === 'review',
                'ready' => ($this->reviewStatuses[$section->id] ?? $section->review_status) === 'ready',
                default => true,
            };

            return $matchesSearch && $matchesFilter;
        })->values();

        if ($this->writingMode !== 'focus') {
            return $sections;
        }

        $focusId = $this->focusSectionId ?: $sections->first()?->id;

        return $sections->where('id', $focusId)->values();
    }

    public function setWritingMode(string $mode): void
    {
        if (! in_array($mode, ['edit', 'review', 'focus'], true)) {
            return;
        }

        $this->writingMode = $mode;

        if ($mode === 'focus') {
            $this->focusSectionId ??= $this->getSections()->first()?->id;
        }
    }

    public function enterFocusMode(int $sectionId): void
    {
        if (! $this->sectionsQuery()->whereKey($sectionId)->exists()) {
            return;
        }

        $this->focusSectionId = $sectionId;
        $this->writingMode = 'focus';
    }

    public function moveFocus(int $direction): void
    {
        $sections = $this->getSections()->values();
        if ($sections->isEmpty()) {
            return;
        }

        $currentIndex = $sections->search(fn (ProjectApplicationSection $section) => $section->id === $this->focusSectionId);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        $nextIndex = max(0, min($sections->count() - 1, $currentIndex + $direction));
        $this->focusSectionId = $sections[$nextIndex]->id;
        $this->writingMode = 'focus';
    }

    public function getTemplates(): array
    {
        return ApplicationTemplates::list();
    }

    public function getSelectedTemplateInfo(): ?array
    {
        return ApplicationTemplates::get($this->selectedTemplate);
    }

    public function getTemplateCatalog(): array
    {
        return ApplicationTemplates::catalog();
    }

    public function selectTemplate(string $key): void
    {
        $normalised = ApplicationTemplates::normaliseKey($key);

        if (ApplicationTemplates::get($normalised)) {
            $this->selectedTemplate = $normalised;
        }
    }

    public function getTemplateAlignment(): array
    {
        return $this->templateAlignment($this->selectedTemplate);
    }

    protected function templateAlignment(?string $templateKey = null): array
    {
        $template = ApplicationTemplates::get($templateKey ?: $this->selectedTemplate);
        $officialSections = collect($template['sections'] ?? []);
        $officialKeys = $officialSections->pluck('key')->filter()->values();
        $sections = $this->getSections();
        $sectionsByKey = $sections->whereNotNull('question_key')->keyBy('question_key');

        $matched = 0;
        $needsMetadataSync = 0;
        $missing = [];

        foreach ($officialSections as $official) {
            $existing = $sectionsByKey->get($official['key']);

            if (! $existing) {
                $missing[] = $official;
                continue;
            }

            $matched++;

            if (
                ($existing->category !== ($official['category'] ?? null))
                || ((int) $existing->char_limit !== (int) ($official['char_limit'] ?? 0))
            ) {
                $needsMetadataSync++;
            }
        }

        $custom = $sections->filter(fn (ProjectApplicationSection $section) => ! $section->question_key || ! $officialKeys->contains($section->question_key))->values();
        $answered = $sections->filter(fn (ProjectApplicationSection $section) => filled(trim(strip_tags($section->content ?? ''))))->count();
        $officialCount = $officialSections->count();

        return [
            'official_count' => $officialCount,
            'matched' => $matched,
            'missing_count' => count($missing),
            'missing' => $missing,
            'metadata_sync_count' => $needsMetadataSync,
            'custom_count' => $custom->count(),
            'answered_count' => $answered,
            'coverage' => $officialCount > 0 ? (int) round($matched / $officialCount * 100) : 0,
            'is_current_project_template' => ApplicationTemplates::normaliseKey($this->record->ka_action) === ApplicationTemplates::normaliseKey($templateKey ?: $this->selectedTemplate),
        ];
    }

    public function getHasContentProperty(): bool
    {
        return $this->sectionsQuery()
            ->where('content', '!=', '')
            ->whereNotNull('content')
            ->exists();
    }

    public function getApplicationSummary(): array
    {
        $sections = $this->getSections();
        $completed = 0;
        $overLimit = 0;
        $words = 0;
        $ready = 0;
        $inReview = 0;

        foreach ($sections as $section) {
            $text = trim(strip_tags($this->content[$section->id] ?? (string) $section->content));
            $characters = mb_strlen($text);

            if ($text !== '') {
                $completed++;
                $words += count(preg_split('/\s+/', $text));
            }

            if ($section->char_limit && $characters > $section->char_limit) {
                $overLimit++;
            }

            $status = $this->reviewStatuses[$section->id] ?? $section->review_status;
            $ready += $status === 'ready' ? 1 : 0;
            $inReview += $status === 'review' ? 1 : 0;
        }

        $total = $sections->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'remaining' => $total - $completed,
            'over_limit' => $overLimit,
            'words' => $words,
            'progress' => $total > 0 ? (int) round($completed / $total * 100) : 0,
            'ready' => $ready,
            'in_review' => $inReview,
        ];
    }

    public function getConsistencyReview(): array
    {
        $sections = $this->getSections();
        $issues = [];
        $alignment = $this->templateAlignment($this->record->ka_action ?: $this->selectedTemplate);

        if ($alignment['missing_count'] > 0) {
            $issues[] = $this->reviewIssue(
                'critical',
                'Template structure',
                $alignment['missing_count'].' official template questions are missing from this draft.',
                'Open Template manager and synchronise the selected template before final review.'
            );
        }

        $emptyOfficial = $sections->filter(fn (ProjectApplicationSection $section) => $section->question_key && blank(trim(strip_tags($this->content[$section->id] ?? (string) $section->content))));
        if ($emptyOfficial->isNotEmpty()) {
            $issues[] = $this->reviewIssue(
                'critical',
                'Unanswered questions',
                $emptyOfficial->count().' official questions do not have an answer yet.',
                'Use the “Unanswered” filter and complete the missing sections.',
                $emptyOfficial->first()?->id
            );
        }

        $overLimit = $sections->filter(function (ProjectApplicationSection $section) {
            $text = strip_tags($this->content[$section->id] ?? (string) $section->content);

            return $section->char_limit && mb_strlen($text) > $section->char_limit;
        });
        if ($overLimit->isNotEmpty()) {
            $issues[] = $this->reviewIssue(
                'critical',
                'Character limits',
                $overLimit->count().' answers exceed the configured character limit.',
                'Use the “Over character limit” filter and shorten those answers.',
                $overLimit->first()?->id
            );
        }

        $needsReview = $sections->filter(fn (ProjectApplicationSection $section) => ($this->reviewStatuses[$section->id] ?? $section->review_status) === 'review');
        if ($needsReview->isNotEmpty()) {
            $issues[] = $this->reviewIssue(
                'warning',
                'Internal review',
                $needsReview->count().' sections are still marked as “Needs review”.',
                'Resolve reviewer notes and mark the final answers as Ready.',
                $needsReview->first()?->id
            );
        }

        $shortAnswers = $sections->filter(function (ProjectApplicationSection $section) {
            $text = trim(strip_tags($this->content[$section->id] ?? (string) $section->content));

            return $section->question_key && $text !== '' && str_word_count($text) < 45;
        });
        if ($shortAnswers->isNotEmpty()) {
            $issues[] = $this->reviewIssue(
                'suggestion',
                'Thin answers',
                $shortAnswers->count().' official answers are very short.',
                'Check whether they include context, method, responsibilities, evidence and expected results.',
                $shortAnswers->first()?->id
            );
        }

        $allText = $this->normalisedApplicationText($sections);
        foreach ($this->expectedThemeChecks() as $check) {
            if (! $this->textContainsAny($allText, $check['keywords'])) {
                $issues[] = $this->reviewIssue(
                    $check['severity'],
                    $check['area'],
                    $check['title'],
                    $check['action']
                );
            }
        }

        $this->addCrossSectionIssues($issues, $sections);

        if ((float) $this->record->total_budget <= 0 && (float) $this->record->approved_budget <= 0) {
            $issues[] = $this->reviewIssue(
                'suggestion',
                'Budget consistency',
                'No requested or approved budget is recorded on the project yet.',
                'Add a budget estimate so the application narrative can be checked against the financial plan.'
            );
        }

        $severityCounts = collect($issues)->countBy('severity');
        $score = max(0, 100
            - (($severityCounts->get('critical', 0) ?? 0) * 14)
            - (($severityCounts->get('warning', 0) ?? 0) * 7)
            - (($severityCounts->get('suggestion', 0) ?? 0) * 3)
        );

        return [
            'score' => $score,
            'status' => $score >= 85 ? 'Strong draft' : ($score >= 65 ? 'Needs focused review' : 'Not ready yet'),
            'critical' => (int) ($severityCounts->get('critical', 0) ?? 0),
            'warning' => (int) ($severityCounts->get('warning', 0) ?? 0),
            'suggestion' => (int) ($severityCounts->get('suggestion', 0) ?? 0),
            'issues' => collect($issues)->sortBy(fn (array $issue) => ['critical' => 0, 'warning' => 1, 'suggestion' => 2][$issue['severity']] ?? 3)->values()->all(),
        ];
    }

    public function getQualityReview(): array
    {
        $sections = $this->getSections();
        $text = $this->normalisedApplicationText($sections);
        $summary = $this->getApplicationSummary();
        $criteria = collect($this->qualityCriteria())->map(function (array $criterion) use ($text, $summary) {
            return $this->scoreQualityCriterion($criterion, $text, $summary);
        })->values();

        $weighted = $criteria->sum(fn (array $criterion) => $criterion['score'] * $criterion['weight']);
        $weightTotal = max(1, $criteria->sum('weight'));
        $overall = (int) round($weighted / $weightTotal);

        return [
            'score' => $overall,
            'status' => $overall >= 85 ? 'Evaluator-ready shape' : ($overall >= 70 ? 'Promising, needs polish' : ($overall >= 50 ? 'Needs stronger argumentation' : 'Early draft')),
            'criteria' => $criteria->all(),
        ];
    }

    protected function scoreQualityCriterion(array $criterion, string $text, array $summary): array
    {
        $checks = collect($criterion['checks'])->map(function (array $check) use ($text) {
            $passed = $this->textContainsAny($text, $check['keywords']);

            return [
                'label' => $check['label'],
                'passed' => $passed,
                'recommendation' => $check['recommendation'],
            ];
        });

        $passedCount = $checks->where('passed', true)->count();
        $checkCount = max(1, $checks->count());
        $completionBoost = min(20, (int) round(($summary['progress'] ?? 0) / 5));
        $readinessBoost = min(10, (int) (($summary['ready'] ?? 0) * 2));
        $score = min(100, (int) round(20 + ($passedCount / $checkCount * 70) + $completionBoost + $readinessBoost));
        $missing = $checks->where('passed', false)->pluck('recommendation')->values()->all();

        return [
            'key' => $criterion['key'],
            'label' => $criterion['label'],
            'weight' => $criterion['weight'],
            'score' => $score,
            'status' => $score >= 85 ? 'Strong' : ($score >= 70 ? 'Good base' : ($score >= 50 ? 'Needs work' : 'Weak')),
            'passed' => $passedCount,
            'total' => $checkCount,
            'missing' => array_slice($missing, 0, 2),
            'checks' => $checks->all(),
        ];
    }

    protected function qualityCriteria(): array
    {
        return [
            [
                'key' => 'relevance',
                'label' => 'Relevance',
                'weight' => 25,
                'checks' => [
                    ['label' => 'Needs are evidenced', 'keywords' => ['need', 'needs analysis', 'evidence', 'context', 'challenge', 'barrier'], 'recommendation' => 'Strengthen the needs analysis with concrete evidence, target-group realities or partner observations.'],
                    ['label' => 'Objectives are clear', 'keywords' => ['objective', 'aim', 'specific goal', 'we want to achieve'], 'recommendation' => 'Make the objectives more explicit, measurable and linked to the identified needs.'],
                    ['label' => 'Programme priorities are visible', 'keywords' => ['erasmus', 'priority', 'inclusion', 'participation', 'digital', 'green'], 'recommendation' => 'Show how the project contributes to Erasmus+ priorities through design choices, not slogans.'],
                    ['label' => 'Target groups are defined', 'keywords' => ['target group', 'participants', 'young people', 'youth workers', 'profile'], 'recommendation' => 'Define the exact target groups, their profile and why they need this intervention.'],
                ],
            ],
            [
                'key' => 'design',
                'label' => 'Project design',
                'weight' => 25,
                'checks' => [
                    ['label' => 'Activities are concrete', 'keywords' => ['activity', 'workshop', 'mobility', 'session', 'method'], 'recommendation' => 'Describe the activities with format, methods, participant role and expected learning.'],
                    ['label' => 'Preparation and support are planned', 'keywords' => ['preparation', 'support', 'mentoring', 'briefing', 'follow-up'], 'recommendation' => 'Clarify preparation, support during activities and follow-up after mobility.'],
                    ['label' => 'Learning is recognised', 'keywords' => ['learning outcome', 'youthpass', 'recognition', 'reflection', 'competence'], 'recommendation' => 'Explain how learning outcomes will be reflected on, documented and recognised.'],
                    ['label' => 'Safety and quality are addressed', 'keywords' => ['safety', 'risk', 'safeguarding', 'insurance', 'quality'], 'recommendation' => 'Add risk management, safety procedures and quality-control responsibilities.'],
                ],
            ],
            [
                'key' => 'management',
                'label' => 'Management & partnership',
                'weight' => 20,
                'checks' => [
                    ['label' => 'Roles are distributed', 'keywords' => ['role', 'responsibility', 'task', 'coordinator', 'partner'], 'recommendation' => 'Assign clear responsibilities to each partner/team member and show decision flow.'],
                    ['label' => 'Coordination is credible', 'keywords' => ['coordination', 'communication', 'meeting', 'monitoring', 'timeline'], 'recommendation' => 'Describe communication rhythm, monitoring moments and escalation routes.'],
                    ['label' => 'Budget/logistics are connected', 'keywords' => ['budget', 'logistics', 'travel', 'accommodation', 'venue', 'financial'], 'recommendation' => 'Connect practical arrangements and budget logic to the activities described.'],
                    ['label' => 'Evaluation is planned', 'keywords' => ['evaluation', 'indicator', 'measure', 'assess', 'feedback'], 'recommendation' => 'Add indicators, evidence sources and timing for evaluation.'],
                ],
            ],
            [
                'key' => 'impact',
                'label' => 'Impact',
                'weight' => 20,
                'checks' => [
                    ['label' => 'Results are specific', 'keywords' => ['result', 'output', 'impact', 'outcome', 'change'], 'recommendation' => 'Define concrete results and expected changes for participants, organisations and community.'],
                    ['label' => 'Dissemination is planned', 'keywords' => ['dissemination', 'visibility', 'share', 'audience', 'channel'], 'recommendation' => 'Specify audiences, channels, messages, owners and proof of reach.'],
                    ['label' => 'Sustainability is credible', 'keywords' => ['sustainability', 'continue', 'follow-up', 'after the project', 'long-term'], 'recommendation' => 'Explain what continues after funding, who owns it and with what resources.'],
                    ['label' => 'Participant transfer is visible', 'keywords' => ['transfer', 'local action', 'community', 'multiplier', 'apply'], 'recommendation' => 'Show how participants will use and transfer learning after the activity.'],
                ],
            ],
            [
                'key' => 'inclusion_sustainability',
                'label' => 'Inclusion & sustainability',
                'weight' => 10,
                'checks' => [
                    ['label' => 'Inclusion measures are concrete', 'keywords' => ['inclusion', 'fewer opportunities', 'accessibility', 'barrier', 'support needs'], 'recommendation' => 'Name barriers and practical support measures for participants with fewer opportunities.'],
                    ['label' => 'Green/digital choices are practical', 'keywords' => ['green', 'sustainable', 'digital', 'virtual', 'environment'], 'recommendation' => 'Add practical green and digital choices that genuinely improve implementation.'],
                    ['label' => 'Participation is meaningful', 'keywords' => ['participation', 'co-create', 'decision', 'involved', 'youth-led'], 'recommendation' => 'Show how participants are involved in planning, implementation and follow-up decisions.'],
                ],
            ],
        ];
    }

    protected function addCrossSectionIssues(array &$issues, Collection $sections): void
    {
        $objectives = $this->textForSections($sections, ['objective', 'need', 'rationale']);
        $impact = $this->textForSections($sections, ['impact', 'result', 'follow-up', 'sustainability']);
        $evaluation = $this->textForSections($sections, ['evaluation', 'assess', 'indicator']);
        $participants = $this->textForSections($sections, ['participant', 'target group', 'young people', 'youth worker']);
        $support = $this->textForSections($sections, ['support', 'preparation', 'safety', 'inclusion']);

        if ($objectives !== '' && $impact === '') {
            $issues[] = $this->reviewIssue('warning', 'Objectives to impact', 'Objectives are drafted, but the impact/follow-up sections are still empty.', 'Make sure every objective has a visible expected result and follow-up measure.');
        }

        if ($objectives !== '' && $evaluation === '') {
            $issues[] = $this->reviewIssue('warning', 'Evaluation logic', 'Objectives are drafted, but no evaluation or indicator section is filled yet.', 'Add indicators, evidence sources, timing and responsible persons for the main objectives.');
        }

        if ($participants !== '' && $support === '') {
            $issues[] = $this->reviewIssue('warning', 'Participant support', 'Participant profiles are mentioned, but preparation/support/safety answers look empty.', 'Connect each participant profile to concrete support, preparation and safeguarding measures.');
        }
    }

    protected function textForSections(Collection $sections, array $needles): string
    {
        return $sections
            ->filter(function (ProjectApplicationSection $section) use ($needles) {
                $haystack = mb_strtolower(($section->question_key ?? '').' '.($section->title ?? '').' '.($section->category ?? ''));

                return collect($needles)->contains(fn (string $needle) => str_contains($haystack, $needle));
            })
            ->map(fn (ProjectApplicationSection $section) => trim(strip_tags($this->content[$section->id] ?? (string) $section->content)))
            ->filter()
            ->implode("\n\n");
    }

    protected function normalisedApplicationText(Collection $sections): string
    {
        return Str::of($sections->map(fn (ProjectApplicationSection $section) => $this->content[$section->id] ?? (string) $section->content)->implode("\n\n"))
            ->lower()
            ->ascii()
            ->toString();
    }

    protected function textContainsAny(string $text, array $keywords): bool
    {
        return collect($keywords)->contains(fn (string $keyword) => str_contains($text, Str::of($keyword)->lower()->ascii()->toString()));
    }

    protected function expectedThemeChecks(): array
    {
        return [
            ['severity' => 'warning', 'area' => 'Inclusion', 'title' => 'The draft does not clearly mention inclusion or fewer-opportunity barriers.', 'action' => 'Add barriers, selection/access measures and dignified support for participants with fewer opportunities.', 'keywords' => ['inclusion', 'fewer opportunities', 'barrier', 'accessibility', 'disadvantaged']],
            ['severity' => 'warning', 'area' => 'Learning recognition', 'title' => 'The draft does not clearly mention Youthpass or learning recognition.', 'action' => 'Explain reflection, documentation and recognition of learning outcomes.', 'keywords' => ['youthpass', 'learning outcome', 'recognition', 'reflect', 'reflection']],
            ['severity' => 'warning', 'area' => 'Visibility and dissemination', 'title' => 'The draft does not clearly mention dissemination or visibility.', 'action' => 'Define audiences, channels, outputs, timing, owners and evidence of reach.', 'keywords' => ['dissemination', 'visibility', 'share results', 'communication campaign', 'social media']],
            ['severity' => 'warning', 'area' => 'Safety and risk', 'title' => 'The draft does not clearly mention risk, safety or safeguarding.', 'action' => 'Add safety roles, emergency procedures, insurance, consent and risk mitigation.', 'keywords' => ['risk', 'safety', 'safeguarding', 'protection', 'emergency', 'insurance']],
            ['severity' => 'suggestion', 'area' => 'Green and digital', 'title' => 'Green travel, sustainability or digital/virtual components are not visible yet.', 'action' => 'Add practical measures only where they genuinely support the project design.', 'keywords' => ['green', 'sustainable', 'sustainability', 'digital', 'virtual', 'environment']],
        ];
    }

    protected function reviewIssue(string $severity, string $area, string $title, string $action, ?int $sectionId = null): array
    {
        return [
            'severity' => $severity,
            'area' => $area,
            'title' => $title,
            'action' => $action,
            'section_id' => $sectionId,
        ];
    }

    /** Mirror the DB rows into the bound arrays (mount + after structural changes). */
    protected function loadState(): void
    {
        $this->content = [];
        $this->titles = [];
        $this->reviewStatuses = [];
        $this->internalNotes = [];

        foreach ($this->sectionsQuery()->get() as $s) {
            $this->content[$s->id] = (string) $s->content;
            $this->titles[$s->id] = (string) $s->title;
            $this->reviewStatuses[$s->id] = (string) ($s->review_status ?: 'draft');
            $this->internalNotes[$s->id] = (string) $s->internal_notes;
        }
    }

    // ─── Auto-save hooks ───
    public function updatedContent($value, $key): void
    {
        $this->persistField((int) $key, 'content', (string) $value);
    }

    public function updatedTitles($value, $key): void
    {
        $this->persistField((int) $key, 'title', (string) $value);
    }

    public function updatedReviewStatuses($value, $key): void
    {
        if (! in_array($value, ['draft', 'review', 'ready'], true)) {
            return;
        }
        $this->persistField((int) $key, 'review_status', (string) $value);
    }

    public function updatedInternalNotes($value, $key): void
    {
        $this->persistField((int) $key, 'internal_notes', (string) $value);
    }

    protected function persistField(int $id, string $field, string $value): void
    {
        $this->authorizeProjectManagement();
        $sec = $this->sectionsQuery()->find($id);
        if (! $sec) {
            return;
        }
        $sec->{$field} = $value;
        $sec->save();
        $this->lastSavedAt = now()->format('H:i:s');
    }

    // ─── Library picker ───
    public function openLibrary(int $sectionId): void
    {
        $this->libraryTargetId = $sectionId;
        $this->librarySearch = '';
        $this->showLibrary = true;
    }

    public function closeLibrary(): void
    {
        $this->showLibrary = false;
        $this->libraryTargetId = null;
    }

    public function getLibraryBlocks()
    {
        $keys = ApplicationTemplates::libraryKeys($this->record->ka_action);

        return ContentBlock::query()
            ->where('workspace_id', Filament::getTenant()?->id)
            ->whereIn('ka_action', $keys)
            ->when($this->librarySearch !== '', function ($q) {
                $s = $this->librarySearch;
                $q->where(fn ($q2) => $q2->where('title', 'like', "%{$s}%")->orWhere('body', 'like', "%{$s}%"));
            })
            ->orderByDesc('is_proven')
            ->orderBy('category')
            ->orderBy('title')
            ->get();
    }

    public function insertBlock(int $blockId): void
    {
        $this->authorizeProjectManagement();
        $block = ContentBlock::where('workspace_id', Filament::getTenant()?->id)->find($blockId);
        if (! $block || ! $this->libraryTargetId) {
            return;
        }

        $sec = $this->sectionsQuery()->find($this->libraryTargetId);
        if (! $sec) {
            return;
        }

        $existing = $this->content[$sec->id] ?? (string) $sec->content;
        $new = trim($existing) === '' ? $block->body : rtrim($existing)."\n\n".$block->body;

        $this->content[$sec->id] = $new;
        $sec->content = $new;
        $sec->save();

        $block->increment('usage_count');

        $this->showLibrary = false;
        $this->libraryTargetId = null;

        Notification::make()->title('Inserted from library')->success()->send();
    }

    // ─── Template + sections ───
    public function openTemplateDetails(): void
    {
        $this->showTemplateDetails = true;
    }

    public function closeTemplateDetails(): void
    {
        $this->showTemplateDetails = false;
    }

    public function openReviewDetails(): void
    {
        $this->showReviewDetails = true;
    }

    public function closeReviewDetails(): void
    {
        $this->showReviewDetails = false;
    }

    public function loadTemplate(): void
    {
        $this->authorizeProjectManagement();
        $sections = ApplicationTemplates::sections($this->selectedTemplate);
        if (empty($sections)) {
            return;
        }

        $this->createSnapshot('Before template sync');

        $existing = $this->sectionsQuery()->get();
        $existingByKey = $existing->whereNotNull('question_key')->keyBy('question_key');
        $existingByTitle = $existing->keyBy(fn ($section) => mb_strtolower(trim($section->title)));
        $added = 0;
        $updated = 0;

        $i = ($existing->max('sort_order') ?? -1) + 1;
        foreach ($sections as $sec) {
            $match = $existingByKey->get($sec['key'])
                ?? $existingByTitle->get(mb_strtolower(trim($sec['title'])));

            if ($match) {
                $match->update([
                    'question_key' => $sec['key'],
                    'category' => $sec['category'] ?? null,
                    'char_limit' => $sec['char_limit'] ?? null,
                ]);
                $updated++;

                continue;
            }

            ProjectApplicationSection::create([
                'project_id' => $this->record->id,
                'question_key' => $sec['key'],
                'title' => $sec['title'],
                'category' => $sec['category'] ?? null,
                'char_limit' => $sec['char_limit'] ?? null,
                'content' => '',
                'sort_order' => $i++,
            ]);
            $added++;
        }

        $this->record->ka_action = $this->selectedTemplate;
        $this->record->save();
        $this->record->refresh();

        $this->loadState();

        $this->showTemplateDetails = false;
        Notification::make()
            ->title('Template synchronised')
            ->body("{$added} questions added, {$updated} matched. Existing answers were preserved.")
            ->success()->send();
    }

    public function getQuestionGuidance(ProjectApplicationSection $section): ?string
    {
        if (! $section->question_key) {
            return null;
        }

        $template = ApplicationTemplates::get($this->record->ka_action ?: $this->selectedTemplate);
        $question = collect($template['sections'] ?? [])->firstWhere('key', $section->question_key);

        return $question['guidance'] ?? null;
    }

    public function saveVersion(): void
    {
        $this->authorizeProjectManagement();
        $label = trim($this->versionLabel) ?: 'Manual version '.now()->format('d M Y, H:i');
        $this->createSnapshot($label);
        $this->versionLabel = '';
        Notification::make()->title('Version saved')->success()->send();
    }

    protected function createSnapshot(string $label): ProjectApplicationVersion
    {
        return ProjectApplicationVersion::create([
            'project_id' => $this->record->id,
            'created_by' => auth()->id(),
            'label' => $label,
            'template_key' => $this->record->ka_action,
            'snapshot' => $this->sectionsQuery()->get()->map(fn ($section) => $section->only([
                'question_key', 'title', 'content', 'review_status', 'internal_notes',
                'char_limit', 'category', 'sort_order',
            ]))->values()->all(),
        ]);
    }

    public function getVersions()
    {
        return ProjectApplicationVersion::where('project_id', $this->record->id)
            ->with('creator')->latest()->limit(20)->get();
    }

    public function restoreVersion(int $versionId): void
    {
        $this->authorizeProjectManagement();
        $version = ProjectApplicationVersion::where('project_id', $this->record->id)->findOrFail($versionId);
        $this->createSnapshot('Automatic backup before restore');

        ProjectApplicationSection::where('project_id', $this->record->id)->delete();
        foreach ($version->snapshot as $section) {
            ProjectApplicationSection::create(array_merge($section, ['project_id' => $this->record->id]));
        }
        if ($version->template_key) {
            $this->record->update(['ka_action' => $version->template_key]);
            $this->selectedTemplate = ApplicationTemplates::normaliseKey($version->template_key);
        }

        $this->showVersions = false;
        $this->loadState();
        Notification::make()->title('Version restored')->body('A backup of the previous state was created automatically.')->success()->send();
    }

    public function addSection(): void
    {
        $this->authorizeProjectManagement();
        $maxSort = ProjectApplicationSection::where('project_id', $this->record->id)->max('sort_order') ?? -1;
        ProjectApplicationSection::create([
            'project_id' => $this->record->id,
            'question_key' => 'custom-'.str()->uuid(),
            'title' => 'New section',
            'content' => '',
            'sort_order' => $maxSort + 1,
        ]);

        $this->loadState();
    }

    public function deleteSection(int $id): void
    {
        $this->authorizeProjectManagement();
        ProjectApplicationSection::where('project_id', $this->record->id)->where('id', $id)->delete();

        $this->loadState();
    }

    /**
     * Move a section up or down by swapping sort_order with its neighbour.
     * $direction: -1 = up, +1 = down.
     */
    public function moveSection(int $id, int $direction): void
    {
        $this->authorizeProjectManagement();
        $sections = $this->sectionsQuery()->get()->values();

        $index = $sections->search(fn ($s) => $s->id === $id);
        if ($index === false) {
            return;
        }

        $swapIndex = $index + $direction;
        if ($swapIndex < 0 || $swapIndex >= $sections->count()) {
            return;
        }

        $current = $sections[$index];
        $neighbour = $sections[$swapIndex];

        // Swap their sort_order values.
        $currentOrder = $current->sort_order;
        $current->sort_order = $neighbour->sort_order;
        $neighbour->sort_order = $currentOrder;

        // If orders were equal/null, normalise the whole list to be safe.
        if ($current->sort_order === $neighbour->sort_order) {
            foreach ($sections as $i => $s) {
                $s->sort_order = $i;
                $s->save();
            }
            // re-apply the swap after normalising
            $sections = $this->sectionsQuery()->get()->values();
            $current = $sections[$index];
            $neighbour = $sections[$swapIndex];
            $tmp = $current->sort_order;
            $current->sort_order = $neighbour->sort_order;
            $neighbour->sort_order = $tmp;
        }

        $current->save();
        $neighbour->save();

        $this->loadState();
    }
}
