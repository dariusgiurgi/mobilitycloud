<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProjectBoard;
use App\Filament\Resources\Projects\Pages\ViewProjectEstimate;
use App\Filament\Resources\Projects\Pages\ViewProjectDocuments;
use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Filament\Resources\Projects\Pages\ViewProjectParticipants;
use App\Filament\Resources\Projects\Pages\WriteApplication;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = 'Manage';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema { return ProjectForm::configure($schema); }
    public static function table(Table $table): Table { return ProjectsTable::configure($table); }

    // Budget baskets live in the Budget module, not in Settings.
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'    => ListProjects::route('/'),
            'create'   => CreateProject::route('/create'),
            'overview' => ViewProjectOverview::route('/{record}'),
            'write'    => WriteApplication::route('/{record}/write'),
            'estimate' => ViewProjectEstimate::route('/{record}/estimate'),
            'board'    => ViewProjectBoard::route('/{record}/board'),
            'participants' => ViewProjectParticipants::route('/{record}/participants'),
            'documents' => ViewProjectDocuments::route('/{record}/documents'),
            'edit'     => EditProject::route('/{record}/edit'),
        ];
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        $record = $page->getRecord();

        // Budget points to the estimator while writing, to the board once managed.
        $budgetUrl = $record->isWritingStage()
            ? static::getUrl('estimate', ['record' => $record])
            : static::getUrl('board', ['record' => $record]);

        return [
            NavigationItem::make('Overview')
                ->icon(Heroicon::OutlinedHome)
                ->url(static::getUrl('overview', ['record' => $record]))
                ->isActiveWhen(fn () => $page instanceof ViewProjectOverview),

            NavigationItem::make('Application')
                ->icon(Heroicon::OutlinedDocumentText)
                ->url(static::getUrl('write', ['record' => $record]))
                ->isActiveWhen(fn () => $page instanceof WriteApplication),

            NavigationItem::make('Budget')
                ->icon(Heroicon::OutlinedBanknotes)
                ->url($budgetUrl)
                ->isActiveWhen(fn () => $page instanceof ViewProjectEstimate || $page instanceof ViewProjectBoard),

            NavigationItem::make('Participants')
                ->icon(Heroicon::OutlinedUsers)
                ->url(static::getUrl('participants', ['record' => $record]))
                ->isActiveWhen(fn () => $page instanceof ViewProjectParticipants),

            NavigationItem::make('Documents')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->url(static::getUrl('documents', ['record' => $record]))
                ->isActiveWhen(fn () => $page instanceof ViewProjectDocuments),

            NavigationItem::make('Settings')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->url(static::getUrl('edit', ['record' => $record]))
                ->isActiveWhen(fn () => $page instanceof EditProject),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([ SoftDeletingScope::class ]);
    }
}
