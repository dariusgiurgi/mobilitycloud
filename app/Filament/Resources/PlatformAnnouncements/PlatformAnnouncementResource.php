<?php

namespace App\Filament\Resources\PlatformAnnouncements;

use App\Filament\Resources\PlatformAnnouncements\Pages\CreatePlatformAnnouncement;
use App\Filament\Resources\PlatformAnnouncements\Pages\EditPlatformAnnouncement;
use App\Filament\Resources\PlatformAnnouncements\Pages\ListPlatformAnnouncements;
use App\Models\PlatformAnnouncement;
use App\Models\User;
use App\Services\PlatformCommunicationDeliveryService;
use App\Support\PlanCatalog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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

    protected static string|\UnitEnum|null $navigationGroup = 'Platform management';

    protected static ?string $navigationLabel = 'Communications';

    protected static ?string $modelLabel = 'communication';

    protected static ?string $pluralModelLabel = 'communications';

    protected static ?int $navigationSort = 30;

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
            Section::make('Communication')
                ->description('Send an in-app notification, show a banner in the platform header, or do both.')
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
                        ->native(false)
                        ->required(),
                    Select::make('delivery_type')
                        ->label('Delivery')
                        ->options(PlatformAnnouncement::DELIVERY_TYPES)
                        ->default('banner')
                        ->native(false)
                        ->required(),
                    Select::make('audience')
                        ->options(PlatformAnnouncement::AUDIENCES)
                        ->default('all')
                        ->required()
                        ->native(false)
                        ->live(),
                    Select::make('plans')
                        ->multiple()
                        ->options(PlanCatalog::planOptions())
                        ->visible(fn (callable $get): bool => $get('audience') === 'plans')
                        ->columnSpanFull(),
                    Select::make('target_user_ids')
                        ->label('Recipients')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => User::query()
                            ->whereNull('archived_at')
                            ->orderBy('name')
                            ->limit(250)
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [$user->id => $user->name.' · '.$user->email])
                            ->all())
                        ->visible(fn (callable $get): bool => $get('audience') === 'selected_users')
                        ->required(fn (callable $get): bool => $get('audience') === 'selected_users')
                        ->columnSpanFull(),
                ]),
            Section::make('Schedule')
                ->description('Schedule applies to banners. Notifications are sent when the communication is created, or manually from the table.')
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
                TextColumn::make('delivery_type')
                    ->label('Delivery')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PlatformAnnouncement::DELIVERY_TYPES[$state ?: 'banner'] ?? ucfirst((string) $state))
                    ->color(fn (?string $state): string => match ($state ?: 'banner') {
                        'notification' => 'info',
                        'both' => 'success',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('notification_sent_count')
                    ->label('Sent')
                    ->state(fn (PlatformAnnouncement $record): string => $record->sendsNotification()
                        ? (string) ($record->notification_sent_count ?? 0)
                        : '—')
                    ->description(fn (PlatformAnnouncement $record): ?string => $record->notification_sent_at?->format('d M Y, H:i'))
                    ->alignEnd(),
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
                Action::make('sendNotificationNow')
                    ->label('Send notification')
                    ->icon('heroicon-o-bell-alert')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(fn (PlatformAnnouncement $record): string => 'Send notification · '.$record->title)
                    ->modalDescription('This will add the message to the in-app notifications of every matching recipient now.')
                    ->visible(fn (PlatformAnnouncement $record): bool => $record->sendsNotification())
                    ->action(function (PlatformAnnouncement $record): void {
                        $sent = app(PlatformCommunicationDeliveryService::class)->sendNotification($record);

                        Notification::make()
                            ->title('Notification sent')
                            ->body($sent.' '.str('recipient')->plural($sent).' reached.')
                            ->success()
                            ->send();
                    }),
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
