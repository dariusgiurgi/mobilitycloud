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

    public bool $showTemplateDetails = false;

    public bool $showVersions = false;

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
        return $this->getSections()->filter(function (ProjectApplicationSection $section) {
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
    }

    public function getTemplates(): array
    {
        return ApplicationTemplates::list();
    }

    public function getSelectedTemplateInfo(): ?array
    {
        return ApplicationTemplates::get($this->selectedTemplate);
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
