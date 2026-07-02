<?php

namespace App\Filament\Resources\PlatformWorkspaces\RelationManagers;

use App\Models\PlatformWorkspaceNote;
use App\Support\PlatformAudit;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkspaceNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'platformNotes';

    protected static ?string $title = 'Workspace notes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category')
                ->options(PlatformWorkspaceNote::categoryOptions())
                ->default('support')
                ->required()
                ->native(false),
            Toggle::make('is_pinned')
                ->label('Pin note')
                ->inline(false),
            Textarea::make('body')
                ->label('Internal workspace note')
                ->rows(5)
                ->required()
                ->maxLength(5000)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderByDesc('is_pinned'))
            ->recordTitleAttribute('body')
            ->columns([
                IconColumn::make('is_pinned')
                    ->label('Pinned')
                    ->boolean(),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PlatformWorkspaceNote::categoryOptions()[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'urgent', 'security' => 'danger',
                        'billing', 'commercial' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('body')
                    ->label('Note')
                    ->limit(140)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->placeholder('System')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->since()
                    ->sortable()
                    ->description(fn (PlatformWorkspaceNote $record): string => $record->created_at?->format('d M Y, H:i') ?? ''),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add workspace note')
                    ->mutateDataUsing(function (array $data): array {
                        $data['author_id'] = auth()->id();

                        return $data;
                    })
                    ->after(function (PlatformWorkspaceNote $record): void {
                        PlatformAudit::log('workspace_note.created', 'Added workspace note for '.$record->workspace?->name, $record->workspace, [
                            'note_id' => $record->id,
                            'category' => $record->category,
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(function (PlatformWorkspaceNote $record): void {
                        PlatformAudit::log('workspace_note.updated', 'Updated workspace note for '.$record->workspace?->name, $record->workspace, [
                            'note_id' => $record->id,
                            'category' => $record->category,
                        ]);
                    }),
                DeleteAction::make()
                    ->after(function (PlatformWorkspaceNote $record): void {
                        PlatformAudit::log('workspace_note.deleted', 'Deleted workspace note for '.$record->workspace?->name, $record->workspace, [
                            'note_id' => $record->id,
                        ]);
                    }),
            ]);
    }
}
