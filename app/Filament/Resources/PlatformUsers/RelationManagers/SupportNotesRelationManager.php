<?php

namespace App\Filament\Resources\PlatformUsers\RelationManagers;

use App\Models\PlatformSupportNote;
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

class SupportNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'supportNotes';

    protected static ?string $title = 'Support timeline';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category')
                ->options(PlatformSupportNote::categoryOptions())
                ->default('support')
                ->required(),
            Toggle::make('is_pinned')
                ->label('Pin note')
                ->inline(false),
            Textarea::make('body')
                ->label('Internal note')
                ->rows(5)
                ->required()
                ->maxLength(5000)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                IconColumn::make('is_pinned')
                    ->label('Pinned')
                    ->boolean(),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PlatformSupportNote::categoryOptions()[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'urgent', 'security' => 'danger',
                        'billing' => 'warning',
                        'bug' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('body')
                    ->label('Note')
                    ->limit(120)
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
                    ->description(fn (PlatformSupportNote $record): string => $record->created_at?->format('d M Y, H:i') ?? ''),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add support note')
                    ->mutateDataUsing(function (array $data): array {
                        $data['author_id'] = auth()->id();

                        return $data;
                    })
                    ->after(function (PlatformSupportNote $record): void {
                        PlatformAudit::log('support_note.created', 'Added support note for '.$record->user?->email, $record->user, [
                            'note_id' => $record->id,
                            'category' => $record->category,
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(function (PlatformSupportNote $record): void {
                        PlatformAudit::log('support_note.updated', 'Updated support note for '.$record->user?->email, $record->user, [
                            'note_id' => $record->id,
                            'category' => $record->category,
                        ]);
                    }),
                DeleteAction::make()
                    ->after(function (PlatformSupportNote $record): void {
                        PlatformAudit::log('support_note.deleted', 'Deleted support note for '.$record->user?->email, $record->user, [
                            'note_id' => $record->id,
                        ]);
                    }),
            ]);
    }
}
