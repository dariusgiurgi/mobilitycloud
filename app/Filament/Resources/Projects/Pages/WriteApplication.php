<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ContentBlock;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectApplicationVersion;
use App\Support\ApplicationTableDefinitions;
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

    /** @var array<int, array<string, array<int, array<string, string>>>> */
    public array $tables = [];

    /** @var array<int, array<string, string|int|bool>> */
    public array $activityFlows = [];

    public string $sectionSearch = '';

    public string $sectionFilter = 'all';

    public string $writingMode = 'edit';

    public ?int $focusSectionId = null;

    public bool $showTemplateDetails = false;

    public bool $showVersions = false;

    public ?int $versionDiffId = null;

    public bool $showReviewDetails = false;

    public bool $showActivityBuilder = false;

    public string $templateCatalogFamily = 'all';

    public string $templateCatalogSearch = '';

    public string $versionLabel = '';

    public ?string $lastSavedAt = null;

    // ─── Library picker state ───
    public bool $showLibrary = false;

    public ?int $libraryTargetId = null;

    public string $librarySearch = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        ProjectResource::ensureProjectAccountTenant($this->record, 'write');
        $this->selectedTemplate = ApplicationTemplates::normaliseKey($this->record->ka_action ?: 'ka152-you');
        if (! ApplicationTemplates::get($this->selectedTemplate) || ! ApplicationTemplates::isOfficiallyVerified($this->selectedTemplate)) {
            $this->selectedTemplate = ApplicationTemplates::defaultVerifiedKey();
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
                'tables' => count($this->getQuestionTables($section)) > 0,
                'official-issues' => collect($this->getOfficialCompletenessReview()['issues'])->pluck('section_id')->filter()->contains($section->id),
                'draft' => ($this->reviewStatuses[$section->id] ?? $section->review_status) === 'draft',
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

    public function filterReviewStatus(string $status): void
    {
        if (! in_array($status, ['all', 'draft', 'review', 'ready', 'tables', 'official-issues'], true)) {
            return;
        }

        $this->sectionFilter = $status;
        $this->writingMode = 'review';
    }

    public function setReviewStatus(int $sectionId, string $status): void
    {
        if (! in_array($status, ['draft', 'review', 'ready'], true)) {
            return;
        }

        $this->reviewStatuses[$sectionId] = $status;
        $this->persistField($sectionId, 'review_status', $status);
    }

    public function markAnsweredSectionsReady(): void
    {
        $this->authorizeProjectManagement();
        $updated = 0;

        foreach ($this->getSections() as $section) {
            $answer = trim(strip_tags($this->content[$section->id] ?? (string) $section->content));
            if ($answer === '') {
                continue;
            }

            $section->review_status = 'ready';
            $section->save();
            $this->reviewStatuses[$section->id] = 'ready';
            $updated++;
        }

        $this->lastSavedAt = now()->format('H:i:s');

        Notification::make()
            ->title('Answered sections marked ready')
            ->body($updated.' sections were moved to Ready.')
            ->success()
            ->send();
    }

    public function sendIssueSectionsToReview(): void
    {
        $this->authorizeProjectManagement();
        $issuesBySection = collect($this->getConsistencyReview()['issues'])
            ->filter(fn (array $issue) => filled($issue['section_id'] ?? null))
            ->groupBy('section_id');

        if ($issuesBySection->isEmpty()) {
            Notification::make()
                ->title('No section issues found')
                ->body('The current checks did not find section-specific issues to send to review.')
                ->success()
                ->send();

            return;
        }

        $updated = 0;
        foreach ($issuesBySection as $sectionId => $issues) {
            $section = $this->sectionsQuery()->find((int) $sectionId);
            if (! $section) {
                continue;
            }

            $section->review_status = 'review';
            $section->internal_notes = $this->reviewNoteWithIssues((string) $section->internal_notes, $issues->all());
            $section->save();

            $this->reviewStatuses[$section->id] = 'review';
            $this->internalNotes[$section->id] = (string) $section->internal_notes;
            $updated++;
        }

        $this->lastSavedAt = now()->format('H:i:s');

        Notification::make()
            ->title('Issues sent to review')
            ->body($updated.' sections were marked as Needs review and received reviewer notes.')
            ->success()
            ->send();
    }

    public function generateReviewNotesFromChecks(): void
    {
        $this->authorizeProjectManagement();
        $issuesBySection = collect($this->getConsistencyReview()['issues'])
            ->filter(fn (array $issue) => filled($issue['section_id'] ?? null))
            ->groupBy('section_id');

        $updated = 0;
        foreach ($issuesBySection as $sectionId => $issues) {
            $section = $this->sectionsQuery()->find((int) $sectionId);
            if (! $section) {
                continue;
            }

            $section->internal_notes = $this->reviewNoteWithIssues((string) $section->internal_notes, $issues->all());
            $section->save();
            $this->internalNotes[$section->id] = (string) $section->internal_notes;
            $updated++;
        }

        $this->lastSavedAt = now()->format('H:i:s');

        Notification::make()
            ->title('Reviewer notes generated')
            ->body($updated.' sections received notes from the current checks.')
            ->success()
            ->send();
    }

    public function getReviewActionSummary(): array
    {
        $sections = $this->getSections();
        $issueSectionIds = collect($this->getConsistencyReview()['issues'])
            ->pluck('section_id')
            ->filter()
            ->unique()
            ->values();

        return [
            'answered' => $sections->filter(fn (ProjectApplicationSection $section) => trim(strip_tags($this->content[$section->id] ?? (string) $section->content)) !== '')->count(),
            'issue_sections' => $issueSectionIds->count(),
            'empty' => $sections->filter(fn (ProjectApplicationSection $section) => trim(strip_tags($this->content[$section->id] ?? (string) $section->content)) === '')->count(),
        ];
    }

    protected function reviewNoteWithIssues(string $existingNote, array $issues): string
    {
        $lines = collect($issues)
            ->map(fn (array $issue) => trim(($issue['area'] ?? 'Review').': '.($issue['title'] ?? '').' '.($issue['action'] ?? '')))
            ->filter()
            ->unique()
            ->map(fn (string $line) => '• '.$line)
            ->values();

        $note = trim($existingNote);
        foreach ($lines as $line) {
            if (! str_contains($note, $line)) {
                $note = trim($note."\n".$line);
            }
        }

        return $note;
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
        $search = Str::of($this->templateCatalogSearch)->lower()->trim()->toString();

        return collect(ApplicationTemplates::catalog())
            ->when($this->templateCatalogFamily !== 'all', fn ($catalog) => $catalog->where('family', $this->templateCatalogFamily))
            ->when($search !== '', function ($catalog) use ($search) {
                return $catalog->filter(function (array $template) use ($search) {
                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $template['key'] ?? null,
                        $template['label'] ?? null,
                        $template['action'] ?? null,
                        $template['description'] ?? null,
                        $template['form_id'] ?? null,
                        $template['family'] ?? null,
                        $template['sector'] ?? null,
                    ])));

                    return str_contains($haystack, $search);
                });
            })
            ->map(function (array $template) {
                $audit = $this->templateAudit($template['key']);

                return [
                    ...$template,
                    'audit_score' => $audit['score'],
                    'audit_status' => $audit['status'],
                    'audit_issue_count' => count($audit['issues']),
                    'audit_table_count' => count($audit['tables']),
                ];
            })
            ->values()
            ->all();
    }

    public function getTemplateFamilies(): array
    {
        return [
            'all' => 'All templates',
            ...ApplicationTemplates::families(),
        ];
    }

    public function selectTemplate(string $key): void
    {
        $normalised = ApplicationTemplates::normaliseKey($key);

        if (ApplicationTemplates::get($normalised) && ApplicationTemplates::isOfficiallyVerified($normalised)) {
            $this->selectedTemplate = $normalised;
        }
    }

    public function getTemplateAlignment(): array
    {
        return $this->templateAlignment($this->selectedTemplate);
    }

    public function getTemplateSwitchPreview(): array
    {
        $template = ApplicationTemplates::get($this->selectedTemplate);
        $officialSections = collect($template['sections'] ?? []);
        $officialKeys = $officialSections->pluck('key')->filter()->values();
        $existing = $this->getSections();
        $existingByKey = $existing->whereNotNull('question_key')->keyBy('question_key');
        $existingByTitle = $existing->keyBy(fn (ProjectApplicationSection $section) => mb_strtolower(trim($section->title)));
        $usedSectionIds = collect();
        $added = [];
        $matched = [];

        foreach ($officialSections as $official) {
            $match = $existingByKey->get($official['key'])
                ?? $existingByTitle->get(mb_strtolower(trim($official['title'])));

            if ($match) {
                $usedSectionIds->push($match->id);
                $matched[] = $official['title'];

                continue;
            }

            $added[] = $official['title'];
        }

        $removed = $existing
            ->filter(fn (ProjectApplicationSection $section) => $section->question_key
                && ! str_starts_with($section->question_key, 'custom-')
                && ! $officialKeys->contains($section->question_key)
                && ! $usedSectionIds->contains($section->id))
            ->pluck('title')
            ->values()
            ->all();

        $custom = $existing
            ->filter(fn (ProjectApplicationSection $section) => ! $section->question_key || str_starts_with($section->question_key, 'custom-'))
            ->count();

        return [
            'added_count' => count($added),
            'matched_count' => count($matched),
            'removed_count' => count($removed),
            'custom_preserved_count' => $custom,
            'added' => array_slice($added, 0, 5),
            'removed' => array_slice($removed, 0, 5),
            'will_change_project_template' => ApplicationTemplates::normaliseKey($this->record->ka_action) !== $this->selectedTemplate,
        ];
    }

    public function getTemplateSourceNotice(): ?array
    {
        $template = ApplicationTemplates::get($this->selectedTemplate);
        if (! $template) {
            return null;
        }

        $latestCallYear = collect(ApplicationTemplates::catalog())->max('call_year') ?: (int) now()->year;
        $callYear = (int) ($template['call_year'] ?? 0);

        if (! ($template['officially_verified'] ?? false)) {
            return [
                'tone' => 'warning',
                'title' => 'Template not verified',
                'body' => 'This structure is not available for synchronisation until every question is checked against the official form.',
            ];
        }

        if ($callYear > 0 && $callYear < $latestCallYear) {
            return [
                'tone' => 'warning',
                'title' => 'Older official source',
                'body' => "The latest verified source currently available for this template is Call {$callYear}. Review the official form before final submission if your call year is {$latestCallYear}.",
            ];
        }

        return [
            'tone' => 'success',
            'title' => 'Officially verified',
            'body' => 'Questions are matched against the official application form stored in the template catalog.',
        ];
    }

    public function getSelectedTemplateAudit(): array
    {
        return $this->templateAudit($this->selectedTemplate);
    }

    public function getTemplateAuditSummary(): array
    {
        $audits = collect(ApplicationTemplates::catalog())
            ->map(fn (array $template) => $this->templateAudit($template['key']));

        return [
            'templates' => $audits->count(),
            'excellent' => $audits->where('score', '>=', 90)->count(),
            'needs_review' => $audits->filter(fn (array $audit) => $audit['score'] < 90)->count(),
            'issues' => $audits->sum(fn (array $audit) => count($audit['issues'])),
            'tables' => $audits->sum(fn (array $audit) => count($audit['tables'])),
        ];
    }

    protected function templateAudit(string $templateKey): array
    {
        $key = ApplicationTemplates::normaliseKey($templateKey);
        $template = ApplicationTemplates::get($key);

        if (! $template) {
            return [
                'score' => 0,
                'status' => 'Missing',
                'issues' => [$this->templateAuditIssue('critical', 'Template not found', 'This template key does not exist in the catalog.')],
                'tables' => [],
                'counts' => ['sections' => 0, 'categories' => 0],
            ];
        }

        $sections = collect($template['sections'] ?? []);
        $issues = [];

        if (! ($template['officially_verified'] ?? false)) {
            $issues[] = $this->templateAuditIssue('critical', 'Not verified', 'This template is hidden from synchronisation until checked against an official source.');
        }

        foreach (['label', 'action', 'call_year', 'form_id', 'source_url', 'description'] as $field) {
            if (blank($template[$field] ?? null)) {
                $issues[] = $this->templateAuditIssue('warning', 'Missing template metadata', "The {$field} metadata field is empty.");
            }
        }

        if ($sections->isEmpty()) {
            $issues[] = $this->templateAuditIssue('critical', 'No questions', 'The template does not contain any application questions.');
        }

        $duplicateKeys = $sections->pluck('key')->filter()->duplicates()->unique()->values()->all();
        foreach ($duplicateKeys as $duplicateKey) {
            $issues[] = $this->templateAuditIssue('critical', 'Duplicate question key', "The key {$duplicateKey} appears more than once.");
        }

        $sections->each(function (array $section, int $index) use (&$issues): void {
            $number = $index + 1;

            if (blank($section['key'] ?? null)) {
                $issues[] = $this->templateAuditIssue('critical', 'Missing question key', "Question {$number} does not have a stable key.");
            }

            if (blank($section['title'] ?? null)) {
                $issues[] = $this->templateAuditIssue('critical', 'Missing question title', "Question {$number} does not have a title.");
            }

            if (blank($section['category'] ?? null)) {
                $issues[] = $this->templateAuditIssue('warning', 'Missing category', "Question {$number} is not grouped into a category.");
            }

            if (blank($section['guidance'] ?? null)) {
                $issues[] = $this->templateAuditIssue('suggestion', 'Missing guidance', "Question {$number} has no writing guidance.");
            }

            if (isset($section['title']) && mb_strlen((string) $section['title']) > 255) {
                $issues[] = $this->templateAuditIssue('suggestion', 'Long official title', "Question {$number} has a long official title; layout must keep it multiline.");
            }
        });

        $tables = $sections
            ->flatMap(function (array $section, int $index) {
                return collect($this->tableDefinitionsForTemplateSection($section))
                    ->map(fn (array $table) => [
                        'section_number' => $index + 1,
                        'question_key' => $section['key'] ?? null,
                        'section_title' => $section['title'] ?? 'Untitled question',
                        'key' => $table['key'],
                        'label' => $table['label'],
                    ]);
            })
            ->unique(fn (array $table) => ($table['question_key'] ?? '').':'.$table['key'])
            ->values()
            ->all();

        $severityCounts = collect($issues)->countBy('severity');
        $score = max(0, 100
            - (($severityCounts->get('critical', 0) ?? 0) * 24)
            - (($severityCounts->get('warning', 0) ?? 0) * 9)
            - (($severityCounts->get('suggestion', 0) ?? 0) * 2)
        );

        return [
            'score' => $score,
            'status' => $score >= 92 ? 'Clean' : ($score >= 78 ? 'Needs review' : 'Risky'),
            'issues' => collect($issues)->sortBy(fn (array $issue) => ['critical' => 0, 'warning' => 1, 'suggestion' => 2][$issue['severity']] ?? 3)->values()->all(),
            'tables' => $tables,
            'counts' => [
                'sections' => $sections->count(),
                'categories' => $sections->pluck('category')->filter()->unique()->count(),
                'with_guidance' => $sections->filter(fn (array $section) => filled($section['guidance'] ?? null))->count(),
                'with_char_limits' => $sections->filter(fn (array $section) => filled($section['char_limit'] ?? null))->count(),
            ],
        ];
    }

    protected function templateAuditIssue(string $severity, string $title, string $description): array
    {
        return compact('severity', 'title', 'description');
    }

    protected function tableDefinitionsForTemplateSection(array $section): array
    {
        $model = new ProjectApplicationSection([
            'question_key' => $section['key'] ?? null,
            'title' => $section['title'] ?? '',
            'category' => $section['category'] ?? null,
        ]);

        return ApplicationTableDefinitions::forSection($model);
    }

    public function isActivityTableTemplate(): bool
    {
        $key = ApplicationTemplates::normaliseKey($this->record->ka_action ?: $this->selectedTemplate);

        return str_starts_with($key, 'ka121-') || str_starts_with($key, 'ka151-');
    }

    public function getActivityTableChecklist(): array
    {
        $key = ApplicationTemplates::normaliseKey($this->record->ka_action ?: $this->selectedTemplate);

        if (str_starts_with($key, 'ka151-')) {
            return [
                'Activity references and order should match the official table.',
                'For each activity, capture format, participant profile, number of participants and duration.',
                'Check travel days, green travel, preparatory visits, inclusion support and exceptional costs separately.',
                'Narrative answers should explain why the activity table choices are coherent with the youth goals.',
            ];
        }

        return [
            'Treat the application as an activity plan: activity type, participants, duration and support measures must stay aligned.',
            'Check individual support, travel, green travel, additional travel days and preparatory visits before export.',
            'Use the written answers to justify the activity choices, not to duplicate the budget table.',
            'Flag inclusion participants and exceptional support early because they affect both quality and budget logic.',
        ];
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
        $draft = 0;
        $noted = 0;

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
            $draft += $status === 'draft' ? 1 : 0;
            $noted += filled(trim($this->internalNotes[$section->id] ?? (string) $section->internal_notes)) ? 1 : 0;
        }

        $total = $sections->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'remaining' => $total - $completed,
            'over_limit' => $overLimit,
            'words' => $words,
            'progress' => $total > 0 ? (int) round($completed / $total * 100) : 0,
            'draft' => $draft,
            'ready' => $ready,
            'in_review' => $inReview,
            'noted' => $noted,
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

        foreach ($sections as $section) {
            if (! $section->question_key) {
                continue;
            }

            $text = trim(strip_tags($this->content[$section->id] ?? (string) $section->content));
            if ($text === '') {
                continue;
            }

            $signalIssue = $this->sectionSignalIssue($section, $text);
            if ($signalIssue) {
                $issues[] = $signalIssue;
            }
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

        foreach ($this->getOfficialCompletenessReview()['issues'] as $officialIssue) {
            $issues[] = $officialIssue;
        }

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

    protected function sectionSignalIssue(ProjectApplicationSection $section, string $text): ?array
    {
        $title = Str::of($section->title.' '.$section->category)->lower()->ascii()->toString();
        $answer = Str::of($text)->lower()->ascii()->toString();
        $expected = [];

        if (str_contains($title, 'objective') || str_contains($title, 'need') || str_contains($title, 'relevance')) {
            $expected['needs/objectives'] = ['need', 'objective', 'target group', 'evidence', 'context'];
        }

        if (str_contains($title, 'impact') || str_contains($title, 'result') || str_contains($title, 'follow')) {
            $expected['impact logic'] = ['impact', 'result', 'indicator', 'follow-up', 'sustainability', 'measure'];
        }

        if (str_contains($title, 'evaluation') || str_contains($title, 'monitor') || str_contains($title, 'quality')) {
            $expected['evaluation'] = ['indicator', 'feedback', 'measure', 'evaluation', 'monitor', 'evidence'];
        }

        if (str_contains($title, 'participant') || str_contains($title, 'fewer opportunities') || str_contains($title, 'inclusion')) {
            $expected['participant support'] = ['participant', 'profile', 'barrier', 'support', 'selection', 'inclusion'];
        }

        if (str_contains($title, 'dissemination') || str_contains($title, 'share')) {
            $expected['dissemination'] = ['audience', 'channel', 'message', 'timeline', 'owner', 'evidence'];
        }

        if (substr_count($section->title, '?') > 1 && substr_count($text, "\n") < 2 && str_word_count($text) < 120) {
            return $this->reviewIssue(
                'suggestion',
                'Subquestions',
                'This official field contains multiple questions, but the answer looks like one short block.',
                'Split the answer by subquestion so the evaluator can easily find every requested element.',
                $section->id
            );
        }

        foreach ($expected as $area => $keywords) {
            if (! $this->textContainsAny($answer, $keywords)) {
                return $this->reviewIssue(
                    'suggestion',
                    Str::title($area),
                    'This answer may be missing one or more expected evaluator signals.',
                    'Add concrete signals such as '.implode(', ', array_slice($keywords, 0, 4)).'.',
                    $section->id
                );
            }
        }

        return null;
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
        $haystack = Str::of($text)->lower()->ascii()->toString();

        return collect($keywords)->contains(fn (string $keyword) => str_contains($haystack, Str::of($keyword)->lower()->ascii()->toString()));
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

    public function getOfficialCompletenessReview(): array
    {
        $sections = $this->getSections();
        $issues = [];

        foreach ($sections as $section) {
            if (! $section->question_key) {
                continue;
            }

            foreach ($this->getQuestionTables($section) as $tableDefinition) {
                $rows = $this->normaliseSectionTables($this->tables[$section->id] ?? $section->application_tables ?? []);
                if (empty($rows[$tableDefinition['key']] ?? [])) {
                    $issues[] = $this->reviewIssue(
                        'warning',
                        'Official table',
                        'The “'.$tableDefinition['label'].'” table is empty.',
                        'Fill this table or use “Populate from project” if the answer requires structured official data.',
                        $section->id
                    );
                }
            }
        }

        foreach ($this->conditionalOfficialRules() as $rule) {
            $trigger = $sections->firstWhere('question_key', $rule['if']);
            if (! $trigger || ! $this->looksAffirmative($this->content[$trigger->id] ?? (string) $trigger->content)) {
                continue;
            }

            foreach ($rule['then'] as $requiredKey) {
                $required = $sections->firstWhere('question_key', $requiredKey);
                if (! $required) {
                    continue;
                }

                $answer = trim(strip_tags($this->content[$required->id] ?? (string) $required->content));
                if ($answer === '') {
                    $issues[] = $this->reviewIssue(
                        'critical',
                        'Conditional official answer',
                        'A previous answer is affirmative, but the linked official follow-up field is empty.',
                        'Complete this follow-up answer so the form logic is coherent.',
                        $required->id
                    );
                }
            }
        }

        if ($this->supportsActivityBuilder() && count($this->normaliseActivityFlows($this->activityFlows)) === 0) {
            $issues[] = $this->reviewIssue(
                'warning',
                'Activities and flows',
                'No activity/flow plan is stored for this application.',
                'Open Activities & flows and define the mobility groups, dates, duration, participants and support needs.'
            );
        }

        $severityCounts = collect($issues)->countBy('severity');
        $score = max(0, 100
            - (($severityCounts->get('critical', 0) ?? 0) * 18)
            - (($severityCounts->get('warning', 0) ?? 0) * 8)
            - (($severityCounts->get('suggestion', 0) ?? 0) * 3)
        );

        return [
            'score' => $score,
            'status' => $score >= 90 ? 'Officially coherent' : ($score >= 72 ? 'Needs official checks' : 'Not submission-ready'),
            'critical' => (int) ($severityCounts->get('critical', 0) ?? 0),
            'warning' => (int) ($severityCounts->get('warning', 0) ?? 0),
            'suggestion' => (int) ($severityCounts->get('suggestion', 0) ?? 0),
            'issues' => collect($issues)->sortBy(fn (array $issue) => ['critical' => 0, 'warning' => 1, 'suggestion' => 2][$issue['severity']] ?? 3)->values()->all(),
        ];
    }

    public function getSubmissionChecklist(): array
    {
        $sections = $this->getSections();
        $alignment = $this->templateAlignment($this->record->ka_action ?: $this->selectedTemplate);
        $summary = $this->getApplicationSummary();
        $officialReview = $this->getOfficialCompletenessReview();
        $activityReview = $this->getActivityFlowReview();
        $action = ApplicationTemplates::normaliseKey($this->record->ka_action ?: $this->selectedTemplate);
        $items = [];

        $items[] = $this->checklistItem(
            $alignment['missing_count'] === 0 && $alignment['metadata_sync_count'] === 0 ? 'complete' : 'missing',
            'Official template structure',
            $alignment['missing_count'] === 0 ? 'All official questions are present and metadata is aligned.' : $alignment['missing_count'].' official questions are missing.',
            'Synchronise the selected template before final submission.'
        );

        $firstEmpty = $sections->first(fn (ProjectApplicationSection $section) => $section->question_key && trim(strip_tags($this->content[$section->id] ?? (string) $section->content)) === '');
        $items[] = $this->checklistItem(
            $firstEmpty ? 'missing' : 'complete',
            'Official answers completed',
            $firstEmpty ? 'At least one official question is still unanswered.' : 'All official questions have an answer.',
            'Use the Unanswered filter and complete missing official fields.',
            $firstEmpty?->id
        );

        $firstOverLimit = $sections->first(function (ProjectApplicationSection $section) {
            $text = strip_tags($this->content[$section->id] ?? (string) $section->content);

            return $section->char_limit && mb_strlen($text) > $section->char_limit;
        });
        $items[] = $this->checklistItem(
            $firstOverLimit ? 'missing' : 'complete',
            'Character limits respected',
            $firstOverLimit ? 'One or more answers exceed their character limit.' : 'No answer exceeds its configured character limit.',
            'Shorten answers flagged as over limit.',
            $firstOverLimit?->id
        );

        $items[] = $this->checklistItem(
            $officialReview['critical'] === 0 && $officialReview['warning'] === 0 ? 'complete' : ($officialReview['critical'] ? 'missing' : 'warning'),
            'Official logic checks',
            $officialReview['critical'] || $officialReview['warning'] ? 'Official readiness still has unresolved checks.' : 'Conditional answers and required tables look coherent.',
            'Open Official readiness and fix conditional answers or empty required tables.'
        );

        if ($this->supportsActivityBuilder()) {
            $items[] = $this->checklistItem(
                $activityReview['score'] >= 90 ? 'complete' : ($activityReview['critical'] ? 'missing' : 'warning'),
                'Activity flows coherent',
                $activityReview['score'] >= 90 ? 'Mobility flows look coherent.' : 'Activity flows need review before submission.',
                'Open Activities & flows and resolve participant, date, country and distance-band issues.'
            );
        }

        $budget = (float) ($this->record->approved_budget ?: $this->record->total_budget);
        $items[] = $this->checklistItem(
            $budget > 0 ? 'complete' : 'warning',
            'Budget baseline present',
            $budget > 0 ? 'A requested or approved budget is recorded.' : 'No requested or approved budget is recorded yet.',
            'Add a budget baseline so narrative and financial logic can be checked.'
        );

        $items[] = $this->checklistItem(
            ($summary['in_review'] ?? 0) === 0 ? 'complete' : 'warning',
            'Internal review queue cleared',
            ($summary['in_review'] ?? 0) === 0 ? 'No section is marked Needs review.' : $summary['in_review'].' sections still need review.',
            'Resolve notes and move final answers to Ready.'
        );

        $items = array_merge($items, $this->actionSpecificChecklistItems($action, $sections));

        $counts = collect($items)->countBy('status');
        $score = max(0, 100
            - (($counts->get('missing', 0) ?? 0) * 14)
            - (($counts->get('warning', 0) ?? 0) * 6)
        );

        return [
            'score' => $score,
            'status' => $score >= 90 ? 'Submission-ready shape' : ($score >= 72 ? 'Almost there' : 'Needs work before submission'),
            'complete' => (int) ($counts->get('complete', 0) ?? 0),
            'warning' => (int) ($counts->get('warning', 0) ?? 0),
            'missing' => (int) ($counts->get('missing', 0) ?? 0),
            'items' => $items,
        ];
    }

    protected function actionSpecificChecklistItems(string $action, Collection $sections): array
    {
        if ($action === 'ka152-you') {
            return $this->ka152SubmissionChecklist($sections);
        }

        return [
            $this->contentChecklistItem($sections, ['evaluation', 'indicator', 'assess'], 'Evaluation logic', 'Evaluation, indicators and evidence are visible.', 'Add indicators, evidence sources, timing and responsible persons.'),
            $this->contentChecklistItem($sections, ['dissemination', 'visible', 'share'], 'Dissemination plan', 'Dissemination and visibility are visible.', 'Define audiences, channels, outputs, timing and proof of reach.'),
            $this->contentChecklistItem($sections, ['safety', 'risk', 'protection'], 'Safety and risk', 'Safety, protection or risk management is visible.', 'Add risk, safety, protection and emergency procedures where applicable.'),
        ];
    }

    protected function ka152SubmissionChecklist(Collection $sections): array
    {
        $participantBackground = $sections->firstWhere('question_key', 'activity-participant-background');
        $participantRows = $participantBackground ? ($this->normaliseSectionTables($this->tables[$participantBackground->id] ?? $participantBackground->application_tables ?? [])['participant_groups'] ?? []) : [];
        $fewerParticipants = $this->record->participants()->where('role', 'participant')->where('fewer_opportunities', true)->count();
        $fewerAnswer = $sections->firstWhere('question_key', 'fewer-opportunities');
        $fewerMeasures = $sections->firstWhere('question_key', 'fewer-opportunities-measures');
        $environment = $sections->firstWhere('question_key', 'environmental-practices');
        $environmentDescription = $sections->firstWhere('question_key', 'environmental-practices-description');
        $europeanCertificates = $sections->firstWhere('question_key', 'european-certificates');
        $europeanCertificatesWhich = $sections->firstWhere('question_key', 'european-certificates-which');

        return [
            $this->checklistItem(
                $this->sectionHasAnswer($participantBackground) || count($participantRows) > 0 ? 'complete' : 'missing',
                'KA152 participant groups',
                'Participant groups, leaders, age profile and selection logic should be explicit.',
                'Complete participant background or the participant groups table.',
                $participantBackground?->id
            ),
            $this->checklistItem(
                $this->sectionHasAnswer($europeanCertificates) && ($this->textContainsAny($this->sectionAnswer($europeanCertificates).' '.$this->sectionAnswer($europeanCertificatesWhich), ['youthpass', 'yes', 'no']) || $this->sectionHasAnswer($europeanCertificatesWhich)) ? 'complete' : 'warning',
                'KA152 learning recognition',
                'Youthpass / European recognition logic should be explicit.',
                'Clarify whether Youthpass or another European instrument will be used.',
                $europeanCertificates?->id
            ),
            $this->checklistItem(
                ($fewerParticipants === 0 && ! $this->looksAffirmative($this->sectionAnswer($fewerAnswer))) || ($this->sectionHasAnswer($fewerMeasures)) ? 'complete' : 'missing',
                'KA152 fewer-opportunities support',
                $fewerParticipants > 0 ? 'Participant records include fewer-opportunity participants.' : 'No fewer-opportunity participants are currently recorded.',
                'If such participants are involved, complete barriers and support measures.',
                $fewerMeasures?->id ?: $fewerAnswer?->id
            ),
            $this->contentChecklistItem($sections, ['safety-protection', 'safety', 'protection'], 'KA152 safety and protection', 'Safety and protection measures are drafted.', 'Add safeguarding roles, emergency procedures, insurance, consent and risk mitigation.'),
            $this->contentChecklistItem($sections, ['evaluation'], 'KA152 evaluation', 'Evaluation logic is drafted.', 'Pair objectives with indicators, evidence sources and review timing.'),
            $this->contentChecklistItem($sections, ['dissemination', 'participant-dissemination'], 'KA152 dissemination', 'Visibility and participant dissemination are drafted.', 'Define audiences, channels, participant roles and evidence of reach.'),
            $this->checklistItem(
                ! $this->looksAffirmative($this->sectionAnswer($environment)) || $this->sectionHasAnswer($environmentDescription) ? 'complete' : 'warning',
                'KA152 green practices',
                'Environmental-friendly practices should be consistent with the answer.',
                'If green practices are planned, describe them and how participants become aware of them.',
                $environmentDescription?->id ?: $environment?->id
            ),
        ];
    }

    protected function contentChecklistItem(Collection $sections, array $needles, string $label, string $completeText, string $action): array
    {
        $section = $sections->first(function (ProjectApplicationSection $section) use ($needles) {
            $haystack = mb_strtolower(($section->question_key ?? '').' '.($section->title ?? '').' '.($section->category ?? ''));

            return collect($needles)->contains(fn (string $needle) => str_contains($haystack, $needle));
        });

        return $this->checklistItem(
            $this->sectionHasAnswer($section) ? 'complete' : 'missing',
            $label,
            $this->sectionHasAnswer($section) ? $completeText : 'This application area is not drafted yet.',
            $action,
            $section?->id
        );
    }

    protected function checklistItem(string $status, string $label, string $description, string $action, ?int $sectionId = null): array
    {
        return compact('status', 'label', 'description', 'action', 'sectionId');
    }

    protected function sectionHasAnswer(?ProjectApplicationSection $section): bool
    {
        return $section && trim(strip_tags($this->content[$section->id] ?? (string) $section->content)) !== '';
    }

    protected function sectionAnswer(?ProjectApplicationSection $section): string
    {
        return $section ? trim(strip_tags($this->content[$section->id] ?? (string) $section->content)) : '';
    }

    protected function conditionalOfficialRules(): array
    {
        return [
            ['if' => 'participant-contributions', 'then' => ['participant-contributions']],
            ['if' => 'european-certificates', 'then' => ['european-certificates-which']],
            ['if' => 'fewer-opportunities', 'then' => ['fewer-opportunities-types', 'fewer-opportunities-measures']],
            ['if' => 'virtual-blended-components', 'then' => ['virtual-blended-description', 'virtual-blended-share']],
            ['if' => 'environmental-practices', 'then' => ['environmental-practices-description']],
        ];
    }

    protected function looksAffirmative(string $text): bool
    {
        $normalised = Str::of($text)->lower()->ascii()->toString();

        return preg_match('/\b(yes|da|we do|we will|planned|foresee|intend|include|use)\b/', $normalised) === 1
            && preg_match('/\b(no|not|nu|none|n\/a|does not|do not)\b/', $normalised) !== 1;
    }

    /** Mirror the DB rows into the bound arrays (mount + after structural changes). */
    protected function loadState(): void
    {
        $this->content = [];
        $this->titles = [];
        $this->reviewStatuses = [];
        $this->internalNotes = [];
        $this->tables = [];

        foreach ($this->sectionsQuery()->get() as $s) {
            $this->content[$s->id] = (string) $s->content;
            $this->titles[$s->id] = (string) $s->title;
            $this->reviewStatuses[$s->id] = (string) ($s->review_status ?: 'draft');
            $this->internalNotes[$s->id] = (string) $s->internal_notes;
            $this->tables[$s->id] = $s->application_tables ?: [];
        }

        $this->activityFlows = $this->normaliseActivityFlows((array) (($this->record->action_data ?? [])['application_flows'] ?? []));
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

    public function updatedTables($value, $key): void
    {
        $sectionId = (int) Str::before((string) $key, '.');
        $this->persistTables($sectionId);
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

    protected function persistTables(int $id): void
    {
        $this->authorizeProjectManagement();
        $sec = $this->sectionsQuery()->find($id);
        if (! $sec) {
            return;
        }

        $sec->application_tables = $this->normaliseSectionTables($this->tables[$id] ?? []);
        $sec->save();
        $this->lastSavedAt = now()->format('H:i:s');
    }

    protected function normaliseSectionTables(array $tables): array
    {
        return collect($tables)
            ->map(fn ($rows) => collect($rows)
                ->map(fn ($row) => collect((array) $row)
                    ->map(fn ($value) => is_scalar($value) ? trim((string) $value) : '')
                    ->all())
                ->filter(fn (array $row) => collect($row)->filter(fn (string $value) => $value !== '')->isNotEmpty())
                ->values()
                ->all())
            ->filter(fn (array $rows) => count($rows) > 0)
            ->all();
    }

    public function getQuestionTables(ProjectApplicationSection $section): array
    {
        return ApplicationTableDefinitions::forSection($section);
    }

    public function getSectionsWithTables(): array
    {
        return $this->getSections()
            ->map(fn (ProjectApplicationSection $section) => [
                'section' => $section,
                'tables' => $this->getQuestionTables($section),
            ])
            ->filter(fn (array $item) => count($item['tables']) > 0)
            ->values()
            ->all();
    }

    public function addTableRow(int $sectionId, string $tableKey): void
    {
        $section = $this->sectionsQuery()->find($sectionId);
        if (! $section) {
            return;
        }

        $definition = collect($this->getQuestionTables($section))->firstWhere('key', $tableKey);
        if (! $definition) {
            return;
        }

        $row = collect($definition['columns'])->mapWithKeys(fn (array $column) => [$column['field'] => ''])->all();
        $this->tables[$sectionId][$tableKey] ??= [];
        $this->tables[$sectionId][$tableKey][] = $row;
        $this->persistTables($sectionId);
    }

    public function removeTableRow(int $sectionId, string $tableKey, int $rowIndex): void
    {
        if (! isset($this->tables[$sectionId][$tableKey][$rowIndex])) {
            return;
        }

        array_splice($this->tables[$sectionId][$tableKey], $rowIndex, 1);
        $this->persistTables($sectionId);
    }

    public function autofillTable(int $sectionId, string $tableKey): void
    {
        $this->authorizeProjectManagement();

        $section = $this->sectionsQuery()->find($sectionId);
        if (! $section || ! collect($this->getQuestionTables($section))->firstWhere('key', $tableKey)) {
            return;
        }

        $rows = $this->autofillRowsForTable($tableKey);
        if (empty($rows)) {
            Notification::make()
                ->title('No project data found')
                ->body('Add participants, budget lines, expenses or project dates first, then refresh this table.')
                ->warning()
                ->send();

            return;
        }

        $this->tables[$sectionId][$tableKey] = $rows;
        $this->persistTables($sectionId);

        Notification::make()
            ->title('Table populated')
            ->body(count($rows).' rows were generated from current project data. You can edit them manually before export.')
            ->success()
            ->send();
    }

    public function getTableAutofillSummary(string $tableKey): ?string
    {
        return match ($tableKey) {
            'project_topics' => 'Suggests topics from filled narrative and Erasmus+ signals.',
            'participant_groups' => 'Uses participants grouped by country/organisation.',
            'additional_funding' => 'Uses Inclusion/Exceptional budget lines and expenses.',
            'fewer_opportunities_support' => 'Uses participants marked as fewer opportunities.',
            'recognition_tools' => 'Uses common Erasmus+ recognition tools and project follow-up.',
            'activity_plan' => 'Uses project dates, partners and participant counts.',
            'work_packages' => 'Uses budget baskets and open tasks as work-package signals.',
            'evaluation_matrix' => 'Uses objectives/evaluation sections and project tasks.',
            'dissemination_plan' => 'Uses partner list, project dates and dissemination tasks.',
            default => null,
        };
    }

    protected function autofillRowsForTable(string $tableKey): array
    {
        $project = $this->record->fresh(['workspace', 'participants', 'budgetLines.expenses', 'tasks.assignee']);

        return match ($tableKey) {
            'project_topics' => $this->projectTopicRows($project),
            'participant_groups' => $this->participantGroupRows($project),
            'additional_funding' => $this->additionalFundingRows($project),
            'fewer_opportunities_support' => $this->fewerOpportunitiesRows($project),
            'recognition_tools' => $this->recognitionToolRows($project),
            'activity_plan' => $this->activityPlanRows($project),
            'work_packages' => $this->workPackageRows($project),
            'evaluation_matrix' => $this->evaluationMatrixRows($project),
            'dissemination_plan' => $this->disseminationPlanRows($project),
            default => [],
        };
    }

    protected function projectTopicRows($project): array
    {
        $text = Str::of($project->description.' '.$project->applicationSections()->pluck('content')->implode(' '))->lower()->ascii()->toString();
        $topics = [];

        foreach ([
            'Inclusion and diversity' => ['inclusion', 'fewer opportunities', 'barrier'],
            'Physical and mental health, well-being' => ['health', 'well-being', 'mental', 'sport', 'outdoor'],
            'Environment and climate change' => ['green', 'sustainable', 'environment', 'climate'],
            'Digital transformation' => ['digital', 'virtual', 'online'],
            'Participation in democratic life' => ['participation', 'civic', 'community'],
            'Key competences development' => ['competence', 'learning outcome', 'skills'],
        ] as $topic => $keywords) {
            if ($this->textContainsAny($text, $keywords)) {
                $topics[] = [
                    'topic' => $topic,
                    'why_relevant' => 'Detected in the current project narrative. Review and keep only if genuinely central.',
                    'where_visible' => 'Objectives, activity design, impact and dissemination.',
                ];
            }
        }

        return array_slice($topics, 0, 3);
    }

    protected function participantGroupRows($project): array
    {
        return $project->participants
            ->groupBy(fn ($participant) => $participant->country ?: $participant->partner_organisation ?: 'Participants')
            ->map(function ($group, string $label) {
                $ages = collect($group)->map(fn ($participant) => $participant->ageAtReference())->filter();

                return [
                    'group' => $label,
                    'participants' => $group->count().' participants',
                    'fewer_opportunities' => (string) $group->where('fewer_opportunities', true)->count(),
                    'age_profile' => $ages->isNotEmpty() ? $ages->min().'-'.$ages->max().' years' : 'To be confirmed',
                    'leaders' => $group->whereIn('role', ['group_leader', 'facilitator', 'trainer', 'accompanying_person'])->count().' support persons',
                    'selection_logic' => 'Generated from participant records. Add motivation, balance and selection criteria manually.',
                ];
            })
            ->values()
            ->all();
    }

    protected function additionalFundingRows($project): array
    {
        return $project->budgetLines
            ->filter(fn ($line) => str_contains(Str::of($line->title)->lower()->ascii()->toString(), 'inclusion')
                || str_contains(Str::of($line->title)->lower()->ascii()->toString(), 'exceptional'))
            ->filter(fn ($line) => (float) $line->allocated_budget > 0 || (float) $line->expenses->sum('amount_eur') > 0)
            ->map(function ($line) use ($project) {
                $expenses = $line->expenses->pluck('description')->filter()->take(3)->implode('; ');
                $fewer = $project->participants->where('fewer_opportunities', true)->count();

                return [
                    'cost_type' => $line->title,
                    'participants' => $fewer > 0 ? $fewer.' participants with fewer opportunities' : $this->participantSummary($project),
                    'description' => $expenses ?: 'Additional support linked to the '.$line->title.' budget basket.',
                    'justification' => 'Generated from the current budget basket. Review eligibility and adapt the justification to the official form.',
                    'estimated_cost' => number_format((float) $line->allocated_budget ?: (float) $line->expenses->sum('amount_eur'), 2).' EUR',
                ];
            })
            ->values()
            ->all();
    }

    protected function activityPlanRows($project): array
    {
        $flowRows = collect($this->normaliseActivityFlows($this->activityFlows))
            ->map(fn (array $flow) => [
                'activity' => $flow['activity_id'] ?: $project->name,
                'type' => $flow['activity_type'] ?: ($project->ka_action ?: 'Mobility activity'),
                'participants' => trim(($flow['participants_count'] ?: '0').' participants'.((int) ($flow['fewer_opportunities_count'] ?? 0) > 0 ? ', '.$flow['fewer_opportunities_count'].' with fewer opportunities' : '')),
                'duration' => trim(($flow['start_date'] ?: 'TBC').' - '.($flow['end_date'] ?: 'TBC').' · '.($flow['duration_days'] ?: '0').' days'),
                'countries' => trim(($flow['origin_country'] ?: 'Origin TBC').' → '.($flow['destination_country'] ?: 'Destination TBC')),
                'responsible' => $flow['responsible'] ?: ($project->workspace?->name ?: 'Coordinator'),
                'output' => $flow['learning_output'] ?: 'Learning activity, reflection and follow-up results.',
            ])
            ->values()
            ->all();

        if (! empty($flowRows)) {
            return $flowRows;
        }

        $participants = $this->participantSummary($project);
        if ($participants === '' && blank($project->mobility_start_date) && blank($project->start_date)) {
            return [];
        }

        $countries = collect($project->partners)->pluck('country')->filter()->unique()->implode(', ');
        $duration = $project->mobility_start_date && $project->mobility_end_date
            ? $project->mobility_start_date->format('d M Y').' - '.$project->mobility_end_date->format('d M Y')
            : ($project->start_date && $project->end_date ? $project->start_date->format('d M Y').' - '.$project->end_date->format('d M Y') : '');

        return [[
            'activity' => $project->name,
            'type' => $project->ka_action ?: 'Mobility activity',
            'participants' => $participants ?: 'Participants to be confirmed',
            'duration' => $duration,
            'countries' => $countries,
            'responsible' => $project->workspace?->name ?: 'Coordinator',
            'output' => $project->description ?: 'Learning activity and follow-up results.',
        ]];
    }

    protected function fewerOpportunitiesRows($project): array
    {
        $participants = $project->participants->where('fewer_opportunities', true);
        if ($participants->isEmpty()) {
            return [];
        }

        return $participants
            ->groupBy(fn ($participant) => $participant->country ?: $participant->partner_organisation ?: 'Participants')
            ->map(fn ($group, string $label) => [
                'barrier_type' => 'To be specified respectfully',
                'participants' => $group->count().' from '.$label,
                'support_measure' => 'Reinforced preparation, mentoring, practical support and follow-up.',
                'phase' => 'Preparation, mobility and follow-up',
                'responsible' => $project->workspace?->name ?: 'Coordinator',
            ])
            ->values()
            ->all();
    }

    protected function recognitionToolRows($project): array
    {
        return [[
            'tool' => 'Youthpass',
            'purpose' => 'Reflect, document and recognise learning outcomes and key competences.',
            'when_used' => 'Introduced during preparation or first day, reflected daily, finalised at the end.',
            'responsible' => $project->workspace?->name ?: 'Coordinator / facilitators',
            'evidence' => 'Reflection journals, daily evaluations, Youthpass certificates.',
        ]];
    }

    protected function workPackageRows($project): array
    {
        $budgetRows = $project->budgetLines
            ->filter(fn ($line) => (float) $line->allocated_budget > 0)
            ->map(fn ($line) => [
                'work_package' => $line->title,
                'objective' => 'Deliver the project activities supported by this budget basket.',
                'activities' => $line->expenses->pluck('description')->filter()->take(4)->implode('; ') ?: 'Activities to be detailed.',
                'lead_partner' => $project->workspace?->name ?: 'Coordinator',
                'outputs' => 'Eligible costs and documented implementation evidence.',
                'budget_logic' => number_format((float) $line->allocated_budget, 2).' EUR allocated.',
            ]);

        if ($budgetRows->isNotEmpty()) {
            return $budgetRows->values()->all();
        }

        return $project->tasks
            ->where('status', '!=', 'completed')
            ->take(5)
            ->map(fn ($task) => [
                'work_package' => $task->title,
                'objective' => $task->description ?: 'Project implementation task.',
                'activities' => $task->title,
                'lead_partner' => $project->workspace?->name ?: 'Coordinator',
                'outputs' => 'Task completed and documented.',
                'budget_logic' => 'To be connected with the budget.',
            ])
            ->values()
            ->all();
    }

    protected function evaluationMatrixRows($project): array
    {
        $sections = $project->applicationSections()
            ->where(function ($query): void {
                $query->where('title', 'like', '%objective%')
                    ->orWhere('title', 'like', '%evaluation%')
                    ->orWhere('title', 'like', '%impact%');
            })
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        $rows = $sections->map(fn ($section) => [
            'objective' => Str::limit($section->title, 90, ''),
            'indicator' => str_contains(Str::of($section->title)->lower()->toString(), 'impact') ? 'Evidence of expected change is collected.' : 'Objective-specific progress indicator is achieved.',
            'evidence' => 'Application answer, participant feedback, attendance/evidence files and project outputs.',
            'timing' => $project->end_date ? 'By '.$project->end_date->format('d M Y') : 'During and after implementation',
            'responsible' => $project->workspace?->name ?: 'Coordinator',
        ])->values();

        if ($rows->isNotEmpty()) {
            return $rows->all();
        }

        return $project->tasks
            ->take(5)
            ->map(fn ($task) => [
                'objective' => $task->title,
                'indicator' => $task->isCompleted() ? 'Completed' : 'Task completed by deadline.',
                'evidence' => $task->description ?: 'Task evidence and internal review.',
                'timing' => $task->due_date?->format('d M Y') ?: 'To be confirmed',
                'responsible' => $task->assignee?->name ?: ($project->workspace?->name ?: 'Coordinator'),
            ])
            ->values()
            ->all();
    }

    protected function disseminationPlanRows($project): array
    {
        $partners = collect($project->partners);
        $rows = $partners->map(fn (array $partner) => [
            'audience' => trim(($partner['name'] ?? 'Partner organisation').' stakeholders'),
            'message' => $project->acronym ?: $project->name,
            'channel' => 'Partner communication channels',
            'timing' => $project->end_date ? 'By '.$project->end_date->format('d M Y') : 'After main activities',
            'owner' => $partner['name'] ?? 'Partner',
            'evidence' => 'Posts, participant stories, attendance, screenshots or analytics.',
        ]);

        if ($rows->isNotEmpty()) {
            return $rows->values()->all();
        }

        if (blank($project->name)) {
            return [];
        }

        return [[
            'audience' => 'Local community and project stakeholders',
            'message' => $project->acronym ?: $project->name,
            'channel' => 'Website, social media, direct events or newsletters',
            'timing' => $project->end_date ? 'By '.$project->end_date->format('d M Y') : 'After main activities',
            'owner' => $project->workspace?->name ?: 'Coordinator',
            'evidence' => 'Screenshots, links, attendance lists and analytics.',
        ]];
    }

    protected function participantSummary($project): string
    {
        $participants = $project->participants;
        if ($participants->isEmpty()) {
            return '';
        }

        return $participants
            ->groupBy(fn ($participant) => $participant->roleLabel())
            ->map(fn ($group, string $role) => $group->count().' '.$role)
            ->implode(', ');
    }

    public function supportsActivityBuilder(): bool
    {
        $action = ApplicationTemplates::normaliseKey($this->record->ka_action ?: $this->selectedTemplate);

        return str_starts_with($action, 'ka15')
            || str_starts_with($action, 'ka121')
            || str_starts_with($action, 'ka122');
    }

    public function openActivityBuilder(): void
    {
        if (! $this->supportsActivityBuilder()) {
            return;
        }

        if (empty($this->activityFlows)) {
            $this->activityFlows = $this->defaultActivityFlows();
        }

        $this->showActivityBuilder = true;
    }

    public function closeActivityBuilder(): void
    {
        $this->showActivityBuilder = false;
    }

    public function addActivityFlow(): void
    {
        $this->authorizeProjectManagement();
        $this->activityFlows[] = $this->blankActivityFlow(count($this->activityFlows) + 1);
        $this->persistActivityFlows();
    }

    public function removeActivityFlow(int $index): void
    {
        $this->authorizeProjectManagement();
        if (! isset($this->activityFlows[$index])) {
            return;
        }

        array_splice($this->activityFlows, $index, 1);
        $this->persistActivityFlows();
    }

    public function updatedActivityFlows(): void
    {
        $this->persistActivityFlows();
    }

    public function syncActivityFlowDurations(): void
    {
        $this->authorizeProjectManagement();
        $this->activityFlows = collect($this->activityFlows)
            ->map(function (array $flow, int $index) {
                $flow = array_merge($this->blankActivityFlow($index + 1), $flow);
                $flow['duration_days'] = $this->calculateFlowDuration($flow) ?: ($flow['duration_days'] ?? '');

                return $flow;
            })
            ->all();

        $this->persistActivityFlows();

        Notification::make()
            ->title('Durations recalculated')
            ->body('Activity flow durations were recalculated from start and end dates.')
            ->success()
            ->send();
    }

    public function generateActivityFlowsFromParticipants(): void
    {
        $this->authorizeProjectManagement();
        $generated = $this->defaultActivityFlows(true);

        if (empty($generated)) {
            Notification::make()
                ->title('No participants found')
                ->body('Add participants first, or create activity flows manually.')
                ->warning()
                ->send();

            return;
        }

        $this->activityFlows = $generated;
        $this->persistActivityFlows();

        Notification::make()
            ->title('Activity flows generated')
            ->body(count($generated).' flow rows were created from participant groups.')
            ->success()
            ->send();
    }

    public function getActivityFlowSummary(): array
    {
        $flows = collect($this->normaliseActivityFlows($this->activityFlows));
        $review = $this->getActivityFlowReview();

        return [
            'count' => $flows->count(),
            'participants' => (int) $flows->sum(fn (array $flow) => (int) ($flow['participants_count'] ?? 0)),
            'fewer' => (int) $flows->sum(fn (array $flow) => (int) ($flow['fewer_opportunities_count'] ?? 0)),
            'green' => $flows->filter(fn (array $flow) => (bool) ($flow['green_travel'] ?? false))->count(),
            'score' => $review['score'],
            'issues' => count($review['issues']),
        ];
    }

    public function getActivityFlowReview(): array
    {
        $project = $this->record->fresh(['participants']);
        $flows = collect($this->normaliseActivityFlows($this->activityFlows));
        $participants = $project->participants;
        $issues = [];

        if ($flows->isEmpty()) {
            $issues[] = $this->activityFlowIssue('warning', 'No flows defined', 'Create at least one mobility flow or generate flows from participants.');
        }

        $flowIds = $flows->pluck('flow_id')->filter();
        foreach ($flowIds->duplicates()->unique()->values() as $duplicateFlowId) {
            $issues[] = $this->activityFlowIssue('critical', 'Duplicate flow ID', "Flow ID {$duplicateFlowId} is used more than once.");
        }

        foreach ($flows as $index => $flow) {
            $label = ($flow['flow_id'] ?: 'Flow '.($index + 1));

            foreach ([
                'group_label' => 'group',
                'origin_country' => 'origin country',
                'destination_country' => 'destination country',
                'start_date' => 'start date',
                'end_date' => 'end date',
            ] as $field => $fieldLabel) {
                if (blank($flow[$field] ?? null)) {
                    $issues[] = $this->activityFlowIssue('warning', "{$label}: missing {$fieldLabel}", 'Complete the mobility flow so exports and official tables remain coherent.');
                }
            }

            if ((int) ($flow['participants_count'] ?? 0) <= 0) {
                $issues[] = $this->activityFlowIssue('critical', "{$label}: no participants", 'Each flow should include the number of young participants or learners involved.');
            }

            if ((int) ($flow['fewer_opportunities_count'] ?? 0) > (int) ($flow['participants_count'] ?? 0)) {
                $issues[] = $this->activityFlowIssue('critical', "{$label}: fewer-opportunity count too high", 'Participants with fewer opportunities cannot be greater than total participants.');
            }

            if (($flow['green_travel'] ?? false) && blank($flow['distance_band'] ?? null)) {
                $issues[] = $this->activityFlowIssue('suggestion', "{$label}: green travel without distance band", 'Add the distance band so travel-budget assumptions can be checked.');
            }

            $duration = $this->calculateFlowDuration($flow);
            if ($duration !== null && (int) ($flow['duration_days'] ?? 0) !== $duration) {
                $issues[] = $this->activityFlowIssue('suggestion', "{$label}: duration mismatch", "The date range suggests {$duration} activity days.");
            }

            if ($this->flowOutsideProjectMobilityDates($flow)) {
                $issues[] = $this->activityFlowIssue('warning', "{$label}: outside project mobility dates", 'The flow dates are outside the project mobility period configured in project settings.');
            }
        }

        $flowParticipants = (int) $flows->sum(fn (array $flow) => (int) ($flow['participants_count'] ?? 0));
        $realParticipants = $participants->where('role', 'participant')->count();
        if ($realParticipants > 0 && $flowParticipants !== $realParticipants) {
            $issues[] = $this->activityFlowIssue('warning', 'Participant count mismatch', "Flows include {$flowParticipants} participants, while participant records include {$realParticipants} participants.");
        }

        $flowFewer = (int) $flows->sum(fn (array $flow) => (int) ($flow['fewer_opportunities_count'] ?? 0));
        $realFewer = $participants->where('role', 'participant')->where('fewer_opportunities', true)->count();
        if ($realFewer > 0 && $flowFewer !== $realFewer) {
            $issues[] = $this->activityFlowIssue('warning', 'Fewer-opportunity count mismatch', "Flows include {$flowFewer}, while participant records include {$realFewer} participants with fewer opportunities.");
        }

        $action = ApplicationTemplates::normaliseKey($project->ka_action ?: $this->selectedTemplate);
        if ($action === 'ka152-you') {
            $originCountries = $flows->pluck('origin_country')->filter()->unique()->count();
            if ($flows->isNotEmpty() && $originCountries < 2) {
                $issues[] = $this->activityFlowIssue('warning', 'Country balance may be incomplete', 'Youth Exchanges normally need a clear participating-group balance across countries.');
            }

            $outOfAgeRange = $participants
                ->where('role', 'participant')
                ->filter(function ($participant) {
                    $age = $participant->ageAtReference();

                    return $age !== null && ($age < 13 || $age > 30);
                });

            if ($outOfAgeRange->isNotEmpty()) {
                $issues[] = $this->activityFlowIssue('critical', 'Youth Exchange age range', $outOfAgeRange->count().' participant records are outside the usual 13-30 Youth Exchange participant age range.');
            }
        }

        $severityCounts = collect($issues)->countBy('severity');
        $score = max(0, 100
            - (($severityCounts->get('critical', 0) ?? 0) * 18)
            - (($severityCounts->get('warning', 0) ?? 0) * 8)
            - (($severityCounts->get('suggestion', 0) ?? 0) * 3)
        );

        return [
            'score' => $score,
            'status' => $score >= 90 ? 'Flow plan looks coherent' : ($score >= 72 ? 'Needs flow review' : 'Flow plan is risky'),
            'critical' => (int) ($severityCounts->get('critical', 0) ?? 0),
            'warning' => (int) ($severityCounts->get('warning', 0) ?? 0),
            'suggestion' => (int) ($severityCounts->get('suggestion', 0) ?? 0),
            'issues' => collect($issues)->sortBy(fn (array $issue) => ['critical' => 0, 'warning' => 1, 'suggestion' => 2][$issue['severity']] ?? 3)->values()->all(),
        ];
    }

    protected function activityFlowIssue(string $severity, string $title, string $description): array
    {
        return compact('severity', 'title', 'description');
    }

    protected function persistActivityFlows(): void
    {
        $this->authorizeProjectManagement();
        $this->activityFlows = $this->normaliseActivityFlows($this->activityFlows);
        $actionData = $this->record->action_data ?? [];
        $actionData['application_flows'] = $this->activityFlows;
        $this->record->action_data = $actionData;
        $this->record->save();
        $this->record->refresh();
        $this->lastSavedAt = now()->format('H:i:s');
    }

    protected function defaultActivityFlows(bool $fromParticipantsOnly = false): array
    {
        $project = $this->record->fresh(['workspace', 'participants']);
        $participants = $project->participants;

        if ($participants->isEmpty()) {
            return $fromParticipantsOnly ? [] : [$this->blankActivityFlow(1)];
        }

        return $participants
            ->groupBy(fn ($participant) => $participant->country ?: $participant->partner_organisation ?: 'Participants')
            ->values()
            ->map(function ($group, int $index) use ($project) {
                $supportRoles = $group->whereIn('role', ['group_leader', 'facilitator', 'trainer', 'accompanying_person']);
                $participantsOnly = $group->where('role', 'participant');

                return array_merge($this->blankActivityFlow($index + 1), [
                    'group_label' => $group->first()?->partner_organisation ?: $group->first()?->country ?: 'Participant group '.($index + 1),
                    'origin_country' => $group->first()?->country ?: '',
                    'destination_country' => collect($project->partners)->pluck('country')->filter()->first() ?: '',
                    'start_date' => $project->mobility_start_date?->format('Y-m-d') ?: '',
                    'end_date' => $project->mobility_end_date?->format('Y-m-d') ?: '',
                    'duration_days' => $project->mobility_start_date && $project->mobility_end_date ? $project->mobility_start_date->diffInDays($project->mobility_end_date) + 1 : '',
                    'participants_count' => max(0, $participantsOnly->count()),
                    'fewer_opportunities_count' => $group->where('fewer_opportunities', true)->count(),
                    'group_leaders_count' => $supportRoles->count(),
                    'responsible' => $project->workspace?->name ?: 'Coordinator',
                ]);
            })
            ->all();
    }

    protected function blankActivityFlow(int $number): array
    {
        return [
            'activity_id' => 'A'.$number,
            'flow_id' => 'F'.$number,
            'activity_type' => $this->record->ka_action ?: 'KA mobility activity',
            'group_label' => '',
            'origin_country' => '',
            'destination_country' => '',
            'start_date' => '',
            'end_date' => '',
            'duration_days' => '',
            'travel_days' => '2',
            'participants_count' => '',
            'fewer_opportunities_count' => '',
            'group_leaders_count' => '',
            'green_travel' => false,
            'distance_band' => '',
            'responsible' => '',
            'learning_output' => '',
        ];
    }

    protected function normaliseActivityFlows(array $flows): array
    {
        return collect($flows)
            ->map(function ($flow, int $index) {
                $flow = array_merge($this->blankActivityFlow($index + 1), (array) $flow);

                foreach ($flow as $key => $value) {
                    if ($key === 'green_travel') {
                        $flow[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    } elseif (is_scalar($value)) {
                        $flow[$key] = trim((string) $value);
                    } else {
                        $flow[$key] = '';
                    }
                }

                $calculatedDuration = $this->calculateFlowDuration($flow);
                if ($calculatedDuration !== null) {
                    $flow['duration_days'] = (string) $calculatedDuration;
                }

                return $flow;
            })
            ->filter(fn (array $flow) => collect($flow)->except(['travel_days', 'green_travel'])->filter(fn ($value) => $value !== '' && $value !== false)->isNotEmpty())
            ->values()
            ->all();
    }

    protected function calculateFlowDuration(array $flow): ?int
    {
        try {
            if (blank($flow['start_date'] ?? null) || blank($flow['end_date'] ?? null)) {
                return null;
            }

            $start = \Carbon\Carbon::parse($flow['start_date'])->startOfDay();
            $end = \Carbon\Carbon::parse($flow['end_date'])->startOfDay();

            if ($end->lt($start)) {
                return null;
            }

            return $start->diffInDays($end) + 1;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function flowOutsideProjectMobilityDates(array $flow): bool
    {
        try {
            if (! $this->record->mobility_start_date || ! $this->record->mobility_end_date || blank($flow['start_date'] ?? null) || blank($flow['end_date'] ?? null)) {
                return false;
            }

            $start = \Carbon\Carbon::parse($flow['start_date'])->startOfDay();
            $end = \Carbon\Carbon::parse($flow['end_date'])->startOfDay();

            return $start->lt($this->record->mobility_start_date->startOfDay())
                || $end->gt($this->record->mobility_end_date->startOfDay());
        } catch (\Throwable) {
            return false;
        }
    }

    public function insertAnswerScaffold(int $sectionId): void
    {
        $this->authorizeProjectManagement();
        $section = $this->sectionsQuery()->find($sectionId);
        if (! $section) {
            return;
        }

        $hints = $this->getQuestionHints($section);
        $scaffold = collect([
            'Context / direct answer:',
            'Evidence and concrete examples:',
            'Responsibilities, timing and method:',
            'Expected result / proof:',
        ])->map(fn (string $line) => $line."\n- ")->implode("\n\n");

        if (! empty($hints)) {
            $scaffold .= "\n\nEvaluator checklist:\n";
            foreach ($hints['expects'] as $hint) {
                $scaffold .= '- '.$hint."\n";
            }
        }

        $existing = trim($this->content[$sectionId] ?? (string) $section->content);
        $new = $existing === '' ? $scaffold : rtrim($existing)."\n\n".$scaffold;
        $this->content[$sectionId] = $new;
        $section->content = $new;
        $section->save();
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
            ->where('owner_id', auth()->id())
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
        $block = ContentBlock::where('owner_id', auth()->id())->find($blockId);
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

    public function focusNextEmpty(): void
    {
        $section = $this->getSections()
            ->first(fn (ProjectApplicationSection $section) => trim(strip_tags($this->content[$section->id] ?? (string) $section->content)) === '');

        if ($section) {
            $this->enterFocusMode($section->id);
        }
    }

    public function focusNextOfficialIssue(): void
    {
        $sectionId = collect($this->getOfficialCompletenessReview()['issues'])->pluck('section_id')->filter()->first();

        if ($sectionId) {
            $this->enterFocusMode((int) $sectionId);
        }
    }

    public function closeReviewDetails(): void
    {
        $this->showReviewDetails = false;
    }

    public function loadTemplate(): void
    {
        $this->authorizeProjectManagement();
        if (! ApplicationTemplates::isOfficiallyVerified($this->selectedTemplate)) {
            Notification::make()
                ->title('Template not verified')
                ->body('This template is not available for synchronisation until its questions are checked against the official application form.')
                ->warning()
                ->send();

            return;
        }

        $sections = ApplicationTemplates::sections($this->selectedTemplate);
        if (empty($sections)) {
            return;
        }

        $this->createSnapshot('Before template switch');

        $existing = $this->sectionsQuery()->get();
        $existingByKey = $existing->whereNotNull('question_key')->keyBy('question_key');
        $existingByTitle = $existing->keyBy(fn ($section) => mb_strtolower(trim($section->title)));
        $officialKeys = collect($sections)->pluck('key')->filter()->values();
        $added = 0;
        $updated = 0;
        $usedSectionIds = collect();

        foreach ($sections as $sortOrder => $sec) {
            $match = $existingByKey->get($sec['key'])
                ?? $existingByTitle->get(mb_strtolower(trim($sec['title'])));

            if ($match) {
                $match->update([
                    'question_key' => $sec['key'],
                    'title' => $sec['title'],
                    'category' => $sec['category'] ?? null,
                    'char_limit' => $sec['char_limit'] ?? null,
                    'sort_order' => $sortOrder,
                ]);
                $usedSectionIds->push($match->id);
                $updated++;

                continue;
            }

            $created = ProjectApplicationSection::create([
                'project_id' => $this->record->id,
                'question_key' => $sec['key'],
                'title' => $sec['title'],
                'category' => $sec['category'] ?? null,
                'char_limit' => $sec['char_limit'] ?? null,
                'content' => '',
                'sort_order' => $sortOrder,
            ]);
            $usedSectionIds->push($created->id);
            $added++;
        }

        $removed = $existing
            ->filter(fn (ProjectApplicationSection $section) => $section->question_key
                && ! str_starts_with($section->question_key, 'custom-')
                && ! $officialKeys->contains($section->question_key)
                && ! $usedSectionIds->contains($section->id))
            ->each
            ->delete()
            ->count();

        $customSortOrder = count($sections);
        $this->sectionsQuery()
            ->get()
            ->filter(fn (ProjectApplicationSection $section) => ! $usedSectionIds->contains($section->id))
            ->sortBy('sort_order')
            ->values()
            ->each(function (ProjectApplicationSection $section) use (&$customSortOrder) {
                $section->update(['sort_order' => $customSortOrder++]);
            });

        $this->record->ka_action = $this->selectedTemplate;
        $this->record->save();
        $this->record->refresh();

        $this->loadState();

        $this->showTemplateDetails = false;
        Notification::make()
            ->title('Template switched')
            ->body("{$added} questions added, {$updated} matched, {$removed} old template questions removed. Custom sections and compatible answers were preserved.")
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

    public function getQuestionHints(ProjectApplicationSection $section): array
    {
        if (! $section->question_key) {
            return [];
        }

        $title = Str::of($section->title.' '.$section->category)->lower()->ascii()->toString();
        $expects = ['Answer every subquestion explicitly and keep the wording close to the official form.'];
        $evidence = ['Use concrete examples, responsibilities, timing and proof sources where relevant.'];
        $avoid = ['Avoid generic Erasmus+ slogans that are not linked to this project.'];

        if (str_contains($title, 'objective') || str_contains($title, 'need') || str_contains($title, 'relevance')) {
            $expects = ['Specific needs, target groups and objectives connected in one logic.', 'Evidence from your organisation, community or previous activities.'];
            $evidence = ['Needs analysis, participant profile, local context, measurable objectives.'];
            $avoid = ['Objectives that sound good but cannot be measured or linked to a need.'];
        } elseif (str_contains($title, 'participant') || str_contains($title, 'inclusion') || str_contains($title, 'fewer opportunities')) {
            $expects = ['Clear participant profile, selection logic and support measures.', 'Barriers described respectfully and linked to practical support.'];
            $evidence = ['Selection criteria, support plan, accessibility measures, safeguarding roles.'];
            $avoid = ['Listing vulnerable groups without explaining how you will support them.'];
        } elseif (str_contains($title, 'activity') || str_contains($title, 'programme') || str_contains($title, 'method')) {
            $expects = ['Concrete activity format, methods, participant role and learning flow.', 'A credible link between activities, objectives and expected results.'];
            $evidence = ['Daily flow, methods, facilitators, materials, preparation and follow-up.'];
            $avoid = ['A timetable without explaining why the methods fit the objectives.'];
        } elseif (str_contains($title, 'partner') || str_contains($title, 'role') || str_contains($title, 'management')) {
            $expects = ['Named roles, decision flow and coordination rhythm.', 'Tasks distributed realistically between coordinator and partners.'];
            $evidence = ['Responsibility matrix, meeting rhythm, monitoring moments, escalation route.'];
            $avoid = ['Saying all partners do everything equally. Evaluators need ownership.'];
        } elseif (str_contains($title, 'impact') || str_contains($title, 'result') || str_contains($title, 'follow')) {
            $expects = ['Expected changes for participants, organisations and community.', 'Indicators and follow-up actions after the funded period.'];
            $evidence = ['Outputs, indicators, local actions, transfer plan, sustainability owner.'];
            $avoid = ['Big impact claims without indicators or concrete follow-up.'];
        } elseif (str_contains($title, 'evaluation') || str_contains($title, 'quality') || str_contains($title, 'monitor')) {
            $expects = ['How quality will be checked before, during and after activities.', 'Indicators, evidence sources and responsible people.'];
            $evidence = ['Feedback tools, observation, attendance, learning reflection, review meetings.'];
            $avoid = ['Only saying you will collect feedback at the end.'];
        } elseif (str_contains($title, 'dissemination') || str_contains($title, 'share')) {
            $expects = ['Audience, channels, message, timing and owner for each dissemination action.', 'How you will prove reach and use results after the project.'];
            $evidence = ['Communication calendar, target audiences, posts/events, analytics, materials.'];
            $avoid = ['Generic “we will post on social media” without audience or evidence.'];
        } elseif (str_contains($title, 'budget') || str_contains($title, 'cost')) {
            $expects = ['Costs justified by activity needs and eligibility logic.', 'Special costs explained separately with clear assumptions.'];
            $evidence = ['Budget category, calculation basis, supplier logic, participant numbers.'];
            $avoid = ['Repeating amounts without explaining why they are needed.'];
        }

        return [
            'expects' => $expects,
            'evidence' => $evidence,
            'avoid' => $avoid,
        ];
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
                'question_key', 'title', 'content', 'application_tables', 'review_status', 'internal_notes',
                'char_limit', 'category', 'sort_order',
            ]))->values()->all(),
        ]);
    }

    public function getVersions()
    {
        return ProjectApplicationVersion::where('project_id', $this->record->id)
            ->with('creator')->latest()->limit(20)->get();
    }

    public function openVersionDiff(int $versionId): void
    {
        if (! ProjectApplicationVersion::where('project_id', $this->record->id)->whereKey($versionId)->exists()) {
            return;
        }

        $this->versionDiffId = $versionId;
        $this->showVersions = true;
    }

    public function closeVersionDiff(): void
    {
        $this->versionDiffId = null;
    }

    public function getVersionDiff(): ?array
    {
        if (! $this->versionDiffId) {
            return null;
        }

        $version = ProjectApplicationVersion::where('project_id', $this->record->id)
            ->with('creator')
            ->find($this->versionDiffId);

        if (! $version) {
            return null;
        }

        $oldSections = collect($version->snapshot ?? [])->map(fn ($section) => (array) $section);
        $currentSections = $this->sectionsQuery()->get()->map(fn (ProjectApplicationSection $section) => $section->only([
            'question_key', 'title', 'content', 'application_tables', 'review_status', 'internal_notes',
            'char_limit', 'category', 'sort_order',
        ]));

        $oldByKey = $oldSections->keyBy(fn (array $section) => $this->snapshotSectionKey($section));
        $currentByKey = $currentSections->keyBy(fn (array $section) => $this->snapshotSectionKey($section));
        $keys = $oldByKey->keys()->merge($currentByKey->keys())->unique()->values();
        $changes = [];

        foreach ($keys as $key) {
            $old = $oldByKey->get($key);
            $current = $currentByKey->get($key);

            if (! $old && $current) {
                $changes[] = [
                    'type' => 'added',
                    'title' => $current['title'] ?? 'Untitled section',
                    'category' => $current['category'] ?? null,
                    'fields' => ['Section added to current draft'],
                    'before' => '',
                    'after' => $this->previewText((string) ($current['content'] ?? '')),
                ];

                continue;
            }

            if ($old && ! $current) {
                $changes[] = [
                    'type' => 'removed',
                    'title' => $old['title'] ?? 'Untitled section',
                    'category' => $old['category'] ?? null,
                    'fields' => ['Section missing from current draft'],
                    'before' => $this->previewText((string) ($old['content'] ?? '')),
                    'after' => '',
                ];

                continue;
            }

            $changedFields = $this->changedSnapshotFields($old, $current);
            if (! empty($changedFields)) {
                $changes[] = [
                    'type' => 'modified',
                    'title' => $current['title'] ?? $old['title'] ?? 'Untitled section',
                    'category' => $current['category'] ?? $old['category'] ?? null,
                    'fields' => $changedFields,
                    'before' => $this->previewText((string) ($old['content'] ?? '')),
                    'after' => $this->previewText((string) ($current['content'] ?? '')),
                ];
            }
        }

        $counts = collect($changes)->countBy('type');
        $unchanged = max(0, $keys->count() - count($changes));

        return [
            'version' => $version,
            'summary' => [
                'added' => (int) ($counts->get('added', 0) ?? 0),
                'removed' => (int) ($counts->get('removed', 0) ?? 0),
                'modified' => (int) ($counts->get('modified', 0) ?? 0),
                'unchanged' => $unchanged,
                'total_changes' => count($changes),
            ],
            'changes' => $changes,
        ];
    }

    protected function snapshotSectionKey(array $section): string
    {
        $key = trim((string) ($section['question_key'] ?? ''));
        if ($key !== '') {
            return 'key:'.$key;
        }

        return 'title:'.mb_strtolower(trim((string) ($section['title'] ?? 'untitled')));
    }

    protected function changedSnapshotFields(?array $old, ?array $current): array
    {
        $labels = [
            'title' => 'Question title',
            'content' => 'Answer',
            'application_tables' => 'Standard tables',
            'review_status' => 'Review status',
            'internal_notes' => 'Internal notes',
            'char_limit' => 'Character limit',
            'category' => 'Category',
            'sort_order' => 'Order',
        ];

        return collect($labels)
            ->filter(fn (string $label, string $field) => $this->normaliseSnapshotValue($old[$field] ?? null) !== $this->normaliseSnapshotValue($current[$field] ?? null))
            ->values()
            ->all();
    }

    protected function normaliseSnapshotValue(mixed $value): string
    {
        if (is_array($value)) {
            ksort($value);

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return trim(strip_tags((string) ($value ?? '')));
    }

    protected function previewText(string $text): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?: ''), 220);
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
        $this->versionDiffId = null;
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
