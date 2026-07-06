<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProjectBoard;
use App\Filament\Resources\Projects\Pages\ViewProjectDocuments;
use App\Filament\Resources\Projects\Pages\ViewProjectEstimate;
use App\Filament\Resources\Projects\Pages\ViewProjectMobility;
use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Filament\Resources\Projects\Pages\ViewProjectParticipants;
use App\Filament\Resources\Projects\Pages\WriteApplication;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use App\Models\User;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Projects';

    protected static ?int $navigationSort = -1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function shouldRegisterNavigation(): bool
    {
        return PlatformAccess::canUse(PlanCatalog::MODULE_PROJECTS);
    }

    public static function canAccess(): bool
    {
        return PlatformAccess::canUse(PlanCatalog::MODULE_PROJECTS);
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    // Budget baskets live in the Budget module, not in Settings.
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'overview' => ViewProjectOverview::route('/{record}'),
            'write' => WriteApplication::route('/{record}/write'),
            'estimate' => ViewProjectEstimate::route('/{record}/estimate'),
            'board' => ViewProjectBoard::route('/{record}/board'),
            'mobility' => ViewProjectMobility::route('/{record}/mobility'),
            'participants' => ViewProjectParticipants::route('/{record}/participants'),
            'documents' => ViewProjectDocuments::route('/{record}/documents'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        $record = $page->getRecord();

        // While writing, this is a grant estimate. Once the project is managed,
        // the same module becomes the implementation budget board.
        $budgetUrl = $record->isWritingStage()
            ? static::projectUrl($record, 'estimate')
            : static::projectUrl($record, 'board');
        $budgetLabel = $record->isWritingStage() ? 'Estimate' : 'Budget';

        return [
            NavigationItem::make('Overview')
                ->icon(Heroicon::OutlinedHome)
                ->url(static::projectUrl($record))
                ->isActiveWhen(fn () => $page instanceof ViewProjectOverview),

            NavigationItem::make('Application')
                ->icon(Heroicon::OutlinedDocumentText)
                ->url(static::projectUrl($record, 'write'))
                ->isActiveWhen(fn () => $page instanceof WriteApplication),

            NavigationItem::make($budgetLabel)
                ->icon(Heroicon::OutlinedBanknotes)
                ->url($budgetUrl)
                ->isActiveWhen(fn () => $page instanceof ViewProjectEstimate || $page instanceof ViewProjectBoard),

            NavigationItem::make('Mobility')
                ->icon(Heroicon::OutlinedMap)
                ->url(static::projectUrl($record, 'mobility'))
                ->isActiveWhen(fn () => $page instanceof ViewProjectMobility),

            NavigationItem::make('Participants')
                ->icon(Heroicon::OutlinedUsers)
                ->url(static::projectUrl($record, 'participants'))
                ->isActiveWhen(fn () => $page instanceof ViewProjectParticipants),

            NavigationItem::make('Documents')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->url(static::projectUrl($record, 'documents'))
                ->isActiveWhen(fn () => $page instanceof ViewProjectDocuments),

            NavigationItem::make('Settings')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->url(static::projectUrl($record, 'edit'))
                ->visible(fn (): bool => $record->canBeManagedBy(auth()->user()))
                ->isActiveWhen(fn () => $page instanceof EditProject),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->visibleToAccount(auth()->user());
    }

    public static function accountUrl(string $page = 'index', array $parameters = [], ?User $user = null): string
    {
        return static::getUrl($page, $parameters);
    }

    public static function projectUrl(Project $project, string $page = 'overview', ?User $user = null): string
    {
        return static::accountUrl($page, ['record' => $project], $user);
    }

    public static function ensureAccountTenant(string $page = 'index', array $parameters = []): void
    {
        // Tenancy was removed from the product architecture. Legacy calls remain
        // harmless while older pages are being simplified around account-owned projects.
    }

    public static function ensureProjectAccountTenant(Project $project, string $page = 'overview'): void
    {
        static::ensureAccountTenant($page, ['record' => $project]);
    }
}
