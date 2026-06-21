<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ContentBlock;
use App\Models\ProjectApplicationSection;
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

    public string $selectedTemplate = 'ka152';

    /** @var array<int, string> sectionId => content (bound to the textareas) */
    public array $content = [];

    /** @var array<int, string> sectionId => title (bound to the title inputs) */
    public array $titles = [];

    // ─── Library picker state ───
    public bool $showLibrary = false;

    public ?int $libraryTargetId = null;

    public string $librarySearch = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->selectedTemplate = $this->record->ka_action ?: 'ka152';
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

    public function getTemplates(): array
    {
        return ApplicationTemplates::list();
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
        }

        $total = $sections->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'remaining' => $total - $completed,
            'over_limit' => $overLimit,
            'words' => $words,
            'progress' => $total > 0 ? (int) round($completed / $total * 100) : 0,
        ];
    }

    /** Mirror the DB rows into the bound arrays (mount + after structural changes). */
    protected function loadState(): void
    {
        $this->content = [];
        $this->titles = [];

        foreach ($this->sectionsQuery()->get() as $s) {
            $this->content[$s->id] = (string) $s->content;
            $this->titles[$s->id] = (string) $s->title;
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

    protected function persistField(int $id, string $field, string $value): void
    {
        $this->authorizeProjectManagement();
        $sec = $this->sectionsQuery()->find($id);
        if (! $sec) {
            return;
        }
        $sec->{$field} = $value;
        $sec->save();
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
        $ka = $this->record->ka_action ?: 'any';

        return ContentBlock::query()
            ->where('workspace_id', Filament::getTenant()?->id)
            ->where(fn ($q) => $q->where('ka_action', $ka)->orWhere('ka_action', 'any'))
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
    public function loadTemplate(): void
    {
        $this->authorizeProjectManagement();
        $sections = ApplicationTemplates::sections($this->selectedTemplate);
        if (empty($sections)) {
            return;
        }

        ProjectApplicationSection::where('project_id', $this->record->id)->delete();

        $i = 0;
        foreach ($sections as $sec) {
            ProjectApplicationSection::create([
                'project_id' => $this->record->id,
                'title' => $sec['title'],
                'category' => $sec['category'] ?? null,
                'char_limit' => $sec['char_limit'] ?? null,
                'content' => '',
                'sort_order' => $i++,
            ]);
        }

        $this->record->ka_action = $this->selectedTemplate;
        $this->record->save();

        $this->loadState();

        Notification::make()->title('Template loaded')->success()->send();
    }

    public function addSection(): void
    {
        $this->authorizeProjectManagement();
        $maxSort = ProjectApplicationSection::where('project_id', $this->record->id)->max('sort_order') ?? -1;
        ProjectApplicationSection::create([
            'project_id' => $this->record->id,
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
