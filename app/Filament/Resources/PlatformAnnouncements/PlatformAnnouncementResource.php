<?php

namespace App\Filament\Resources\PlatformAnnouncements;

use App\Filament\Resources\PlatformAnnouncements\Pages\CreatePlatformAnnouncement;
use App\Filament\Resources\PlatformAnnouncements\Pages\EditPlatformAnnouncement;
use App\Filament\Resources\PlatformAnnouncements\Pages\ListPlatformAnnouncements;
use App\Filament\Resources\PlatformWorkspaces\PlatformWorkspaceResource;
use App\Models\PlatformAnnouncement;
use App\Models\Workspace;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PlatformAnnouncementResource extends Resource
{
    protected static ?string $model = PlatformAnnouncement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdmin() ?? false;
    }

    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Announcement')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('message')
                        ->required()
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    Select::make('severity')
                        ->options(PlatformAnnouncement::SEVERITIES)
                        ->default('info')
                        ->required(),
                    Select::make('audience')
                        ->options(PlatformAnnouncement::AUDIENCES)
                        ->default('all')
                        ->required()
                        ->live(),
                    Select::make('plans')
                        ->multiple()
                        ->options(PlatformWorkspaceResource::planOptions())
                        ->visible(fn (callable $get): bool => $get('audience') === 'plans')
                        ->columnSpanFull(),
                    Select::make('workspace_ids')
                        ->label('Workspaces')
                        ->multiple()
                        ->options(fn (): array => Workspace::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->visible(fn (callable $get): bool => $get('audience') === 'workspaces')
                        ->columnSpanFull(),
                ]),
            Section::make('Schedule')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('starts_at'),
                    DateTimePicker::make('ends_at'),
                    Toggle::make('is_active')
                        ->default(true)
                        ->inline(false),
                    Toggle::make('is_dismissible')
                        ->label('Dismissible')
                        ->default(true)
                        ->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (PlatformAnnouncement $record): string => str($record->message)->limit(90)),
                TextColumn::make('severity')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PlatformAnnouncement::SEVERITIES[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'maintenance', 'warning' => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('audience')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PlatformAnnouncement::AUDIENCES[$state] ?? ucfirst($state))
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Immediately'),
                TextColumn::make('ends_at')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('No end'),
                TextColumn::make('creator.name')
                    ->label('Created by')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->options(PlatformAnnouncement::SEVERITIES),
                SelectFilter::make('audience')
                    ->options(PlatformAnnouncement::AUDIENCES),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformAnnouncements::route('/'),
            'create' => CreatePlatformAnnouncement::route('/create'),
            'edit' => EditPlatformAnnouncement::route('/{record}/edit'),
        ];
    }
}
