<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ContentBlocks\ContentBlockResource;
use App\Filament\Resources\PublicContentBlocks\PublicContentBlockResource;
use App\Models\ContentBlock;
use App\Models\PublicBlockReport;
use App\Models\PublicContentBlock;
use App\Models\User;
use App\Support\AuthorizesWorkspaceManagement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PublicLibrary extends Page
{
    use AuthorizesWorkspaceManagement;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Community';

    protected static ?string $navigationLabel = 'Public Library';

    protected static ?string $title = 'Public Library';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.public-library';

    // Filtre / sortare (legate in blade).
    public string $search = '';

    public string $sort = 'relevant';     // relevant | new | imports

    public string $category = '';

    public ?int $previewId = null;        // blocul deschis in modalul de preview

    public string $kaAction = '';

    public string $language = '';

    public bool $provenOnly = false;

    public bool $officialOnly = false;

    public bool $showFilters = false;

    public ?int $reportingId = null;     // blocul care se raporteaza acum

    public string $reportReason = '';

    public string $reportDetails = '';

    /** @var array<int> ordinea inghetata a ID-urilor cat stai pe pagina */
    public array $orderedIds = [];

    public function mount(): void
    {
        $this->computeOrder();
    }

    public function getBlocks()
    {
        // Daca ordinea nu e inca stabilita, o calculam o data si o inghetam.
        if (empty($this->orderedIds)) {
            $this->computeOrder();
        }

        if (empty($this->orderedIds)) {
            return collect();
        }

        // Aducem blocurile dupa ID-urile inghetate, pastrand exact acea ordine.
        $blocks = PublicContentBlock::query()
            ->with([
                'author',
                'likes' => fn ($query) => $query->where('user_id', auth()->id()),
            ])
            ->where('is_hidden', false)
            ->whereIn('id', $this->orderedIds)
            ->get()
            ->keyBy('id');

        return collect($this->orderedIds)
            ->map(fn ($id) => $blocks->get($id))
            ->filter()
            ->values();
    }

    /** Recalculeaza ordinea (apelat doar la schimbarea filtrelor/sortarii). */
    public function computeOrder(): void
    {
        $query = PublicContentBlock::query()->where('is_hidden', false);

        if ($this->search !== '') {
            $s = $this->search;
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$s}%")
                ->orWhere('body', 'like', "%{$s}%"));
        }

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        if ($this->kaAction !== '') {
            $query->where('ka_action', $this->kaAction);
        }

        if ($this->language !== '') {
            $query->where('language', $this->language);
        }

        if ($this->provenOnly) {
            $query->where('is_proven', true);
        }

        if ($this->officialOnly) {
            $officialId = User::where('email', PublicContentBlock::OFFICIAL_EMAIL)->value('id');
            $query->where('user_id', $officialId);
        }

        switch ($this->sort) {
            case 'new':
                $query->orderByDesc('created_at');
                break;
            case 'imports':
                $query->orderByDesc('import_count')->orderByDesc('likes_count');
                break;
            case 'relevant':
            default:
                $query->orderByRaw('(import_count * 3 + likes_count * 2) DESC')
                    ->orderByDesc('created_at');
                break;
        }

        $this->orderedIds = $query->limit(60)->pluck('id')->all();
    }

    public function updatedSearch(): void
    {
        $this->computeOrder();
    }

    public function updatedSort(): void
    {
        $this->computeOrder();
    }

    public function updatedCategory(): void
    {
        $this->computeOrder();
    }

    public function updatedKaAction(): void
    {
        $this->computeOrder();
    }

    public function updatedLanguage(): void
    {
        $this->computeOrder();
    }

    public function updatedProvenOnly(): void
    {
        $this->computeOrder();
    }

    public function updatedOfficialOnly(): void
    {
        $this->computeOrder();
    }

    public function getCategories(): array
    {
        return PublicContentBlock::CATEGORIES;
    }

    public function getKaActions(): array
    {
        return PublicContentBlock::KA_ACTIONS;
    }

    public function getLanguages(): array
    {
        return PublicContentBlock::LANGUAGES;
    }

    /** Cate filtre sunt active acum (fara cautare si sortare). */
    public function activeFilterCount(): int
    {
        return collect([
            $this->category, $this->kaAction, $this->language,
            $this->provenOnly, $this->officialOnly,
        ])->filter()->count();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->category = '';
        $this->kaAction = '';
        $this->language = '';
        $this->provenOnly = false;
        $this->officialOnly = false;
        $this->sort = 'relevant';
        $this->computeOrder();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('contentLibrary')
                ->label('My content library')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->url(fn (): string => ContentBlockResource::getUrl('index')),
            Action::make('create')
                ->label('Share content')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => PublicContentBlockResource::canCreate())
                ->url(fn () => PublicContentBlockResource::getUrl('create')),
        ];
    }

    public function toggleLike(int $id): void
    {
        $block = PublicContentBlock::where('is_hidden', false)->find($id);
        if (! $block) {
            return;
        }

        $liked = $block->toggleLike(auth()->user());

        if ($liked) {
            Notification::make()->title('Liked')->success()->send();
        }
    }

    public function import(int $id): void
    {
        $this->authorizeWorkspaceManagement();
        $block = PublicContentBlock::where('is_hidden', false)->find($id);
        $workspace = Filament::getTenant();
        if (! $block || ! $workspace) {
            return;
        }

        if (ContentBlock::query()
            ->where('workspace_id', $workspace->id)
            ->where('imported_from_public_id', $block->id)
            ->exists()) {
            Notification::make()
                ->title('Already in your Content Library')
                ->body('Open your local copy to edit or use it.')
                ->info()
                ->send();

            return;
        }

        ContentBlock::create([
            'workspace_id' => $workspace->id,
            'title' => $block->title,
            'category' => $block->category,
            'ka_action' => $block->ka_action,
            'language' => $block->language,
            'body' => $block->body,
            'tags' => $block->tags,
            'is_proven' => $block->is_proven,
            'source_note' => $block->source_note,
            'usage_count' => 0,
            'imported_from_public_id' => $block->id,
        ]);

        $block->increment('import_count');

        Notification::make()
            ->title('Imported to your Content Library')
            ->success()
            ->send();
    }

    public function openPreview(int $id): void
    {
        $this->previewId = $id;
    }

    public function closePreview(): void
    {
        $this->previewId = null;
    }

    public function getPreviewBlock(): ?PublicContentBlock
    {
        return $this->previewId
            ? PublicContentBlock::with('author')->where('is_hidden', false)->find($this->previewId)
            : null;
    }

    public function getImportedBlocks()
    {
        return ContentBlock::query()
            ->where('workspace_id', Filament::getTenant()?->id)
            ->whereNotNull('imported_from_public_id')
            ->get()
            ->keyBy('imported_from_public_id');
    }

    public function getSubheading(): ?string
    {
        return 'Discover reusable application content shared by the MobilityCloud community.';
    }

    public function getReportReasons(): array
    {
        return PublicBlockReport::REASONS;
    }

    public function openReport(int $id): void
    {
        $this->reportingId = $id;
        $this->reportReason = '';
        $this->reportDetails = '';
    }

    public function closeReport(): void
    {
        $this->reportingId = null;
    }

    public function submitReport(): void
    {
        $this->validate([
            'reportReason' => 'required',
        ], [
            'reportReason.required' => 'Please choose a reason.',
        ]);

        $block = PublicContentBlock::where('is_hidden', false)->find($this->reportingId);
        if (! $block) {
            $this->closeReport();

            return;
        }

        // Nu-ti raportezi propriul bloc.
        if ($block->user_id === auth()->id()) {
            Notification::make()->title('You cannot report your own block')->warning()->send();
            $this->closeReport();

            return;
        }

        // Un singur raport per user per bloc.
        if ($block->isReportedBy(auth()->user())) {
            Notification::make()->title('You already reported this block')->warning()->send();
            $this->closeReport();

            return;
        }

        PublicBlockReport::create([
            'user_id' => auth()->id(),
            'public_content_block_id' => $block->id,
            'reason' => $this->reportReason,
            'details' => $this->reportDetails ?: null,
            'status' => PublicBlockReport::STATUS_PENDING,
        ]);

        Notification::make()
            ->title('Report submitted')
            ->body('Thank you. Our team will review this block.')
            ->success()
            ->send();

        $this->closeReport();
    }
}
