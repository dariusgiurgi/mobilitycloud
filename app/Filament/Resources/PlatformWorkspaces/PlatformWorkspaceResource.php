<?php

namespace App\Filament\Resources\PlatformWorkspaces;

use App\Filament\Resources\PlatformWorkspaces\Pages\EditPlatformWorkspace;
use App\Filament\Resources\PlatformWorkspaces\Pages\ListPlatformWorkspaces;
use App\Models\Workspace;
use App\Support\PlatformAudit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Illuminate\Database\Eloquent\Builder;

class PlatformWorkspaceResource extends Resource
{
    protected static ?string $model = Workspace::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform management';

    protected static ?string $navigationLabel = 'Workspaces';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'workspace';

    protected static ?string $pluralModelLabel = 'workspaces';

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdmin() ?? false;
    }

    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Workspace identity')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('billing_name')
                        ->label('Legal name')
                        ->maxLength(255),
                    TextInput::make('billing_vat')
                        ->label('VAT / registration')
                        ->maxLength(255),
                    Textarea::make('billing_address')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Section::make('Subscription')
                ->description('Manual subscription controls until payment processing is integrated.')
                ->columns(3)
                ->schema([
                    Select::make('plan')
                        ->options(self::planOptions())
                        ->required(),
                    Select::make('subscription_status')
                        ->options(self::subscriptionStatusOptions())
                        ->required()
                        ->default('active'),
                    DateTimePicker::make('trial_ends_at')
                        ->label('Trial ends'),
                    DateTimePicker::make('subscription_ends_at')
                        ->label('Subscription ends'),
                    Toggle::make('is_suspended')
                        ->label('Suspended')
                        ->inline(false),
                    Toggle::make('is_internal')
                        ->label('Internal/test workspace')
                        ->inline(false),
                    Textarea::make('internal_notes')
                        ->label('Internal notes')
                        ->rows(5)
                        ->maxLength(3000)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount(['users', 'projects']))
            ->columns([
                TextColumn::make('name')
                    ->label('Workspace')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Workspace $record): string => $record->owner()?->email ?? 'No owner'),
                TextColumn::make('plan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::planOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'writer_pro' => 'success',
                        'writer' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('subscription_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::subscriptionStatusOptions()[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'expired', 'suspended' => 'danger',
                        'trial' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('projects_count')
                    ->label('Projects')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('trial_ends_at')
                    ->label('Trial ends')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('subscription_ends_at')
                    ->label('Subscription ends')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('is_suspended')
                    ->label('Suspended')
                    ->boolean(),
                IconColumn::make('is_internal')
                    ->label('Internal')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->options(self::planOptions()),
                SelectFilter::make('subscription_status')
                    ->options(self::subscriptionStatusOptions()),
                TernaryFilter::make('is_suspended')
                    ->label('Suspended'),
                TernaryFilter::make('is_internal')
                    ->label('Internal/test'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('extendTrial')
                        ->label('Extend trial 14 days')
                        ->icon('heroicon-o-clock')
                        ->action(function (Workspace $record): void {
                            $record->update([
                                'subscription_status' => 'trial',
                                'trial_ends_at' => now()->addDays(14),
                            ]);
                            PlatformAudit::log('workspace.trial_extended', 'Extended trial for '.$record->name, $record);
                            Notification::make()->title('Trial extended')->success()->send();
                        }),
                    Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Workspace $record): void {
                            $record->update(['subscription_status' => 'active', 'is_suspended' => false]);
                            PlatformAudit::log('workspace.activated', 'Activated workspace '.$record->name, $record);
                            Notification::make()->title('Workspace activated')->success()->send();
                        }),
                    Action::make('expire')
                        ->label('Expire')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Workspace $record): void {
                            $record->update(['subscription_status' => 'expired', 'subscription_ends_at' => now()]);
                            PlatformAudit::log('workspace.expired', 'Expired workspace '.$record->name, $record);
                            Notification::make()->title('Workspace expired')->success()->send();
                        }),
                    EditAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function planOptions(): array
    {
        return [
            'free' => 'Free',
            'writer' => 'Writer',
            'writer_pro' => 'Writer Pro',
        ];
    }

    public static function subscriptionStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'trial' => 'Trial',
            'expired' => 'Expired',
            'suspended' => 'Suspended',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformWorkspaces::route('/'),
            'edit' => EditPlatformWorkspace::route('/{record}/edit'),
        ];
    }
}
