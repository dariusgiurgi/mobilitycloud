<?php

namespace App\Filament\Resources\PlatformWorkspaces;

use App\Filament\Resources\PlatformWorkspaces\Pages\EditPlatformWorkspace;
use App\Filament\Resources\PlatformWorkspaces\Pages\ListPlatformWorkspaces;
use App\Filament\Resources\PlatformWorkspaces\Pages\ViewPlatformWorkspace;
use App\Filament\Resources\PlatformWorkspaces\RelationManagers\SubscriptionEventsRelationManager;
use App\Filament\Resources\PlatformWorkspaces\RelationManagers\WorkspaceNotesRelationManager;
use App\Models\Workspace;
use App\Services\DemoWorkspaceResetService;
use App\Support\PlanCatalog;
use App\Support\PlatformAudit;
use App\Support\PlatformSubscriptionTimeline;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    public static function canView(Model $record): bool
    {
        return $record instanceof Workspace && (auth()->user()?->isPlatformAdmin() ?? false);
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
            Section::make('Billing readiness')
                ->description('Commercial metadata for future invoice/payment-provider integration. These fields do not trigger payments.')
                ->columns(3)
                ->schema([
                    Select::make('billing_interval')
                        ->label('Billing interval')
                        ->options(self::billingIntervalOptions())
                        ->native(false)
                        ->placeholder('Not set'),
                    TextInput::make('billing_amount')
                        ->label('Amount')
                        ->numeric()
                        ->minValue(0)
                        ->prefix(fn (?Workspace $record): string => $record?->billing_currency ?: 'EUR'),
                    TextInput::make('billing_currency')
                        ->label('Currency')
                        ->maxLength(3)
                        ->placeholder('EUR')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? mb_strtoupper(trim($state)) : null),
                    TextInput::make('billing_reference')
                        ->label('Internal reference')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('billing_provider')
                        ->label('Provider')
                        ->options(self::billingProviderOptions())
                        ->native(false)
                        ->placeholder('Not connected'),
                    TextInput::make('billing_provider_customer_id')
                        ->label('Provider customer ID')
                        ->maxLength(255),
                    TextInput::make('billing_provider_subscription_id')
                        ->label('Provider subscription ID')
                        ->maxLength(255),
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
                    Select::make('suspension_category')
                        ->label('Suspension category')
                        ->options(self::suspensionCategoryOptions())
                        ->native(false)
                        ->visible(fn (?Workspace $record): bool => (bool) ($record?->is_suspended)),
                    DateTimePicker::make('suspended_at')
                        ->label('Suspended at')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Workspace $record): bool => (bool) ($record?->is_suspended)),
                    Toggle::make('is_internal')
                        ->label('Internal/test workspace')
                        ->inline(false),
                    Select::make('demo_reset_frequency')
                        ->label('Demo reset')
                        ->options(self::demoResetFrequencyOptions())
                        ->default('manual')
                        ->native(false)
                        ->visible(fn (?Workspace $record): bool => self::isDemoWorkspace($record))
                        ->helperText('Controls the automatic sandbox cleanup. Manual keeps the data until an owner resets it.'),
                    DateTimePicker::make('demo_last_reset_at')
                        ->label('Last demo reset')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Workspace $record): bool => self::isDemoWorkspace($record)),
                    Textarea::make('suspension_reason')
                        ->label('Suspension reason')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull()
                        ->visible(fn (?Workspace $record): bool => (bool) ($record?->is_suspended)),
                    Textarea::make('internal_notes')
                        ->label('Internal notes')
                        ->rows(5)
                        ->maxLength(3000)
                        ->columnSpanFull(),
                ]),
            Section::make('Modules and limits')
                ->description('Override plan defaults for this workspace. Leave modules empty only if you intentionally want no modules enabled.')
                ->columns(3)
                ->schema([
                    CheckboxList::make('feature_flags')
                        ->label('Enabled modules')
                        ->options(PlanCatalog::moduleOptions())
                        ->columns(2)
                        ->bulkToggleable()
                        ->helperText('If left unset, MobilityCloud uses the default module set for the selected plan.')
                        ->columnSpanFull(),
                    TextInput::make('plan_limits.projects')
                        ->label('Project limit')
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('plan_limits.members')
                        ->label('Member limit')
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('plan_limits.storage_mb')
                        ->label('Storage MB')
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('plan_limits.documents_per_month')
                        ->label('Documents/month')
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('plan_limits.ai_requests_per_month')
                        ->label('AI requests/month')
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('plan_limits.exports_per_month')
                        ->label('Exports/month')
                        ->numeric()
                        ->minValue(0),
                ]),
            Section::make('Owner-granted access')
                ->description('Only platform owners can grant manual access outside the subscription rules.')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('access_override_ends_at')
                        ->label('Manual access ends')
                        ->disabled(fn (): bool => ! (auth()->user()?->canManagePlatformAdmins() ?? false))
                        ->dehydrated(fn (): bool => auth()->user()?->canManagePlatformAdmins() ?? false)
                        ->helperText('Leave empty for open-ended access. This bypasses expired subscription restrictions.'),
                    Textarea::make('access_override_reason')
                        ->label('Reason for manual access')
                        ->rows(4)
                        ->maxLength(2000)
                        ->disabled(fn (): bool => ! (auth()->user()?->canManagePlatformAdmins() ?? false))
                        ->dehydrated(fn (): bool => auth()->user()?->canManagePlatformAdmins() ?? false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Workspace overview')
                    ->description('Read-only organisational and operational summary.')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Workspace')
                            ->weight('bold'),
                        TextEntry::make('slug')
                            ->label('Slug')
                            ->copyable(),
                        TextEntry::make('owner')
                            ->label('Owner')
                            ->state(fn (Workspace $record): string => $record->owner()?->email ?? '—'),
                        TextEntry::make('users_count')
                            ->label('Users')
                            ->state(fn (Workspace $record): int => $record->users()->count()),
                        TextEntry::make('projects_count')
                            ->label('Projects')
                            ->state(fn (Workspace $record): int => $record->projects()->withTrashed()->count()),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('d M Y'),
                    ]),
                Section::make('Subscription & access')
                    ->description('Current plan, status and access exceptions.')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('plan')
                            ->label('Plan')
                            ->formatStateUsing(fn (?string $state): string => str($state ?: 'free')->replace('_', ' ')->title())
                            ->badge(),
                        TextEntry::make('subscription_status')
                            ->label('Status')
                            ->state(fn (Workspace $record): string => $record->subscriptionStatusLabel())
                            ->badge()
                            ->color(fn (Workspace $record): string => match ($record->subscription_status) {
                                'expired', 'suspended' => 'danger',
                                'trial' => 'warning',
                                'demo' => 'info',
                                default => 'success',
                            }),
                        TextEntry::make('trial_ends_at')
                            ->label('Trial ends')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),
                        TextEntry::make('subscription_ends_at')
                            ->label('Subscription ends')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),
                        TextEntry::make('access_override_reason')
                            ->label('Manual access reason')
                            ->placeholder('—')
                            ->prose(),
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
                        'demo' => 'warning',
                        'writer_pro' => 'success',
                        'writer' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('subscription_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::subscriptionStatusOptions()[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => self::subscriptionStatusColor($state)),
                TextColumn::make('access_state')
                    ->label('Access')
                    ->badge()
                    ->state(fn (Workspace $record): string => self::accessStateLabel($record))
                    ->color(fn (Workspace $record): string => match (self::accessStateLabel($record)) {
                        'Suspended', 'Expired / read-only' => 'danger',
                        'Manual access', 'Trial ending soon', 'Demo workspace' => 'warning',
                        default => 'success',
                    })
                    ->description(fn (Workspace $record): ?string => $record->is_suspended
                        ? ($record->suspension_category ? (self::suspensionCategoryOptions()[$record->suspension_category] ?? $record->suspension_category) : null)
                        : null),
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
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subscription_ends_at')
                    ->label('Subscription ends')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('billing_amount')
                    ->label('Billing')
                    ->state(fn (Workspace $record): string => $record->billing_amount
                        ? ($record->billing_currency ?: 'EUR').' '.number_format((float) $record->billing_amount, 2).($record->billing_interval ? ' / '.$record->billing_interval : '')
                        : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('demo_last_reset_at')
                    ->label('Demo reset')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Never')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_suspended')
                    ->label('Suspended')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_internal')
                    ->label('Internal')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Filter::make('expired_or_read_only')
                    ->label('Expired / read-only')
                    ->query(fn (Builder $query): Builder => $query
                        ->where(function (Builder $query): void {
                            $query
                                ->where('subscription_status', 'expired')
                                ->orWhere(function (Builder $query): void {
                                    $query
                                        ->whereNotNull('subscription_ends_at')
                                        ->where('subscription_ends_at', '<', now());
                                });
                        })
                        ->where(function (Builder $query): void {
                            $query
                                ->whereNull('access_override_reason')
                                ->orWhere(function (Builder $query): void {
                                    $query
                                        ->whereNotNull('access_override_ends_at')
                                        ->where('access_override_ends_at', '<=', now());
                                });
                        })),
                Filter::make('trial_ending_soon')
                    ->label('Trial ending soon')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('subscription_status', 'trial')
                        ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])),
                Filter::make('manual_access')
                    ->label('Manual access')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('access_override_reason')
                        ->where(function (Builder $query): void {
                            $query
                                ->whereNull('access_override_ends_at')
                                ->orWhere('access_override_ends_at', '>', now());
                        })),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View'),
                ActionGroup::make([
                    Action::make('viewSubscription')
                        ->label('View subscription')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->modalHeading(fn (Workspace $record): string => 'Subscription details · '.$record->name)
                        ->modalContent(fn (Workspace $record) => view('filament.modals.platform-workspace-subscription', [
                            'record' => $record->loadMissing(['accessOverrideGrantor', 'suspendedBy'])->loadCount(['users', 'projects']),
                            'planOptions' => self::planOptions(),
                            'moduleOptions' => PlanCatalog::moduleOptions(),
                            'suspensionCategoryOptions' => self::suspensionCategoryOptions(),
                            'accessState' => self::accessStateLabel($record),
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
                    Action::make('extendTrial')
                        ->label('Extend trial 14 days')
                        ->icon('heroicon-o-clock')
                        ->action(function (Workspace $record): void {
                            $record->update([
                                'subscription_status' => 'trial',
                                'trial_ends_at' => now()->addDays(14),
                                'subscription_ends_at' => null,
                                'is_suspended' => false,
                                'suspension_category' => null,
                                'suspension_reason' => null,
                                'suspended_at' => null,
                                'suspended_by' => null,
                            ]);
                            PlatformSubscriptionTimeline::record($record, 'trial_extended', 'Trial extended by 14 days.', [
                                'trial_ends_at' => $record->trial_ends_at?->toISOString(),
                                'unblocked' => true,
                            ]);
                            PlatformAudit::log('workspace.trial_extended', 'Extended trial for '.$record->name, $record);
                            Notification::make()->title('Trial extended')->success()->send();
                        }),
                    Action::make('setTrialPeriod')
                        ->label('Set trial period')
                        ->icon('heroicon-o-calendar-days')
                        ->modalHeading(fn (Workspace $record): string => 'Set trial period · '.$record->name)
                        ->fillForm(fn (Workspace $record): array => [
                            'trial_ends_at' => $record->trial_ends_at ?: now()->addDays(14),
                        ])
                        ->form([
                            DateTimePicker::make('trial_ends_at')
                                ->label('Trial ends')
                                ->required()
                                ->minDate(now()->subDay())
                                ->helperText('Use this when a client needs a custom evaluation period.'),
                        ])
                        ->action(function (Workspace $record, array $data): void {
                            $record->update([
                                'subscription_status' => 'trial',
                                'trial_ends_at' => $data['trial_ends_at'],
                                'subscription_ends_at' => null,
                                'is_suspended' => false,
                                'suspension_category' => null,
                                'suspension_reason' => null,
                                'suspended_at' => null,
                                'suspended_by' => null,
                            ]);

                            PlatformSubscriptionTimeline::record($record, 'trial_updated', 'Trial period updated.', [
                                'trial_ends_at' => $record->trial_ends_at?->toISOString(),
                                'unblocked' => true,
                            ]);
                            PlatformAudit::log('workspace.trial_updated', 'Updated trial period for '.$record->name, $record);
                            Notification::make()->title('Trial period updated')->success()->send();
                        }),
                    Action::make('markDemo')
                        ->label('Owner only · Mark as demo')
                        ->icon('heroicon-o-beaker')
                        ->color('warning')
                        ->visible(fn (): bool => auth()->user()?->canManagePlatformAdmins() ?? false)
                        ->modalHeading(fn (Workspace $record): string => 'Turn '.$record->name.' into a demo workspace?')
                        ->modalDescription('Demo workspaces are for testing and presentations. They get all modules, demo limits and are marked as internal/test. Real client workspaces should not be converted unless this is intentional.')
                        ->form([
                            Textarea::make('internal_notes')
                                ->label('Demo note')
                                ->default('Demo workspace for testing or product presentation.')
                                ->rows(3)
                                ->maxLength(3000),
                            Select::make('demo_reset_frequency')
                                ->label('Reset frequency')
                                ->options(self::demoResetFrequencyOptions())
                                ->default('manual')
                                ->required()
                                ->native(false)
                                ->helperText('Use manual for sales demos you curate yourself. Daily/weekly can be reset by the scheduled command.'),
                        ])
                        ->action(function (Workspace $record, array $data): void {
                            $record->update([
                                'plan' => 'demo',
                                'subscription_status' => 'demo',
                                'feature_flags' => PlanCatalog::defaultModules('demo'),
                                'plan_limits' => PlanCatalog::defaultLimits('demo'),
                                'trial_ends_at' => null,
                                'subscription_ends_at' => null,
                                'is_suspended' => false,
                                'is_internal' => true,
                                'demo_reset_frequency' => $data['demo_reset_frequency'] ?? 'manual',
                                'internal_notes' => $data['internal_notes'] ?: 'Demo workspace for testing or product presentation.',
                                'access_override_ends_at' => null,
                                'access_override_reason' => null,
                                'access_override_granted_by' => null,
                                'suspension_category' => null,
                                'suspension_reason' => null,
                                'suspended_at' => null,
                                'suspended_by' => null,
                            ]);

                            PlatformSubscriptionTimeline::record($record, 'demo_enabled', 'Workspace marked as demo.', [
                                'plan' => 'demo',
                                'demo_reset_frequency' => $record->demo_reset_frequency,
                                'limits' => PlanCatalog::defaultLimits('demo'),
                            ]);
                            PlatformAudit::log('workspace.demo_enabled', 'Marked workspace '.$record->name.' as demo', $record);
                            Notification::make()->title('Demo workspace enabled')->success()->send();
                        }),
                    Action::make('resetDemoData')
                        ->label('Owner only · Reset demo data')
                        ->icon('heroicon-o-arrow-path')
                        ->color('danger')
                        ->visible(fn (Workspace $record): bool => (auth()->user()?->canManagePlatformAdmins() ?? false) && self::isDemoWorkspace($record))
                        ->modalHeading(fn (Workspace $record): string => 'Reset demo sandbox · '.$record->name)
                        ->modalDescription('This removes demo projects, private library blocks, saved calculations and invitations. Workspace members, settings, branding and subscription details are preserved. Use it before demos or after test sessions.')
                        ->form([
                            TextInput::make('confirmation')
                                ->label('Type RESET DEMO to confirm')
                                ->required()
                                ->rule('in:RESET DEMO'),
                        ])
                        ->action(function (Workspace $record, array $data, DemoWorkspaceResetService $resetService): void {
                            $counts = $resetService->reset($record);

                            PlatformAudit::log('workspace.demo_reset', 'Reset demo data for '.$record->name, $record, [
                                'counts' => $counts,
                                'confirmation' => $data['confirmation'],
                            ]);

                            Notification::make()
                                ->title('Demo data reset')
                                ->body($counts['projects'].' projects, '.$counts['content_blocks'].' content blocks, '.$counts['saved_calculations'].' calculations and '.$counts['invitations'].' invitations removed.')
                                ->success()
                                ->send();
                        }),
                    Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Workspace $record): void {
                            $record->update([
                                'subscription_status' => 'active',
                                'trial_ends_at' => null,
                                'subscription_ends_at' => null,
                                'is_suspended' => false,
                                'access_override_ends_at' => null,
                                'access_override_reason' => null,
                                'access_override_granted_by' => null,
                                'suspension_category' => null,
                                'suspension_reason' => null,
                                'suspended_at' => null,
                                'suspended_by' => null,
                            ]);
                            PlatformSubscriptionTimeline::record($record, 'activated', 'Workspace activated and access restored.', [
                                'unblocked' => true,
                            ]);
                            PlatformAudit::log('workspace.activated', 'Activated workspace '.$record->name, $record);
                            Notification::make()->title('Workspace activated')->success()->send();
                        }),
                    Action::make('grantManualAccess')
                        ->label('Owner only · Grant manual access')
                        ->icon('heroicon-o-key')
                        ->color('info')
                        ->visible(fn (): bool => auth()->user()?->canManagePlatformAdmins() ?? false)
                        ->modalHeading(fn (Workspace $record): string => 'Grant manual access · '.$record->name)
                        ->modalDescription('Owner-only override. This bypasses normal billing restrictions for support, partner access or commercial exceptions, so include a clear reason and an end date whenever possible.')
                        ->fillForm(fn (Workspace $record): array => [
                            'plan' => $record->plan ?: 'free',
                            'access_override_ends_at' => $record->access_override_ends_at,
                            'access_override_reason' => $record->access_override_reason,
                            'feature_flags' => $record->feature_flags ?: PlanCatalog::defaultModules((string) ($record->plan ?: 'free')),
                        ])
                        ->form([
                            Select::make('plan')
                                ->label('Plan')
                                ->options(self::planOptions())
                                ->required()
                                ->native(false),
                            DateTimePicker::make('access_override_ends_at')
                                ->label('Manual access ends')
                                ->helperText('Leave empty for open-ended owner-granted access.'),
                            Textarea::make('access_override_reason')
                                ->label('Reason')
                                ->required()
                                ->rows(4)
                                ->maxLength(2000)
                                ->columnSpanFull(),
                            Select::make('feature_flags')
                                ->label('Enabled modules')
                                ->options(PlanCatalog::moduleOptions())
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->columnSpanFull(),
                        ])
                        ->action(function (Workspace $record, array $data): void {
                            $record->update([
                                ...PlanCatalog::workspaceDefaults((string) $data['plan']),
                                'plan' => $data['plan'],
                                'subscription_status' => 'active',
                                'feature_flags' => $data['feature_flags'] ?? PlanCatalog::defaultModules((string) $data['plan']),
                                'access_override_ends_at' => $data['access_override_ends_at'] ?? null,
                                'access_override_reason' => $data['access_override_reason'],
                                'access_override_granted_by' => auth()->id(),
                                'is_suspended' => false,
                                'suspension_category' => null,
                                'suspension_reason' => null,
                                'suspended_at' => null,
                                'suspended_by' => null,
                            ]);

                            PlatformSubscriptionTimeline::record($record, 'manual_access_granted', 'Owner granted manual access.', [
                                'plan' => $record->plan,
                                'access_override_ends_at' => $record->access_override_ends_at?->toISOString(),
                                'reason' => $record->access_override_reason,
                            ]);
                            PlatformAudit::log('workspace.manual_access_granted', 'Granted manual access for '.$record->name, $record);
                            Notification::make()->title('Manual access granted')->success()->send();
                        }),
                    Action::make('suspend')
                        ->label('Suspend')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->modalHeading(fn (Workspace $record): string => 'Suspend '.$record->name.'?')
                        ->modalDescription('Suspended workspaces lose access to client modules until reactivated or granted manual access. Existing project data is preserved, but normal users are blocked from using the workspace.')
                        ->form([
                            Select::make('suspension_category')
                                ->label('Reason category')
                                ->options(self::suspensionCategoryOptions())
                                ->required()
                                ->native(false),
                            Textarea::make('suspension_reason')
                                ->label('Internal reason')
                                ->required()
                                ->rows(4)
                                ->maxLength(2000)
                                ->columnSpanFull(),
                            TextInput::make('confirmation')
                                ->label('Type SUSPEND to confirm')
                                ->required()
                                ->rule('in:SUSPEND')
                                ->helperText('This prevents accidental access blocking from the workspace table.'),
                        ])
                        ->action(function (Workspace $record, array $data): void {
                            $record->update([
                                'subscription_status' => 'suspended',
                                'is_suspended' => true,
                                'suspension_category' => $data['suspension_category'],
                                'suspension_reason' => $data['suspension_reason'],
                                'suspended_at' => now(),
                                'suspended_by' => auth()->id(),
                            ]);

                            PlatformSubscriptionTimeline::record($record, 'suspended', 'Workspace suspended: '.(self::suspensionCategoryOptions()[$data['suspension_category']] ?? $data['suspension_category']).'.', [
                                'category' => $data['suspension_category'],
                                'reason' => $data['suspension_reason'],
                            ]);
                            PlatformAudit::log('workspace.suspended', 'Suspended workspace '.$record->name, $record, [
                                'category' => $data['suspension_category'],
                            ]);
                            Notification::make()->title('Workspace suspended')->success()->send();
                        }),
                    Action::make('expire')
                        ->label('Expire')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->modalHeading(fn (Workspace $record): string => 'Mark '.$record->name.' as expired?')
                        ->modalDescription('This immediately marks the subscription as expired and sets the end date to now. Use this for billing corrections or ended contracts, not for temporary support holds.')
                        ->form([
                            TextInput::make('confirmation')
                                ->label('Type EXPIRE to confirm')
                                ->required()
                                ->rule('in:EXPIRE'),
                        ])
                        ->action(function (Workspace $record): void {
                            $record->update(['subscription_status' => 'expired', 'subscription_ends_at' => now()]);
                            PlatformSubscriptionTimeline::record($record, 'expired', 'Workspace marked as expired.', [
                                'subscription_ends_at' => $record->subscription_ends_at?->toISOString(),
                            ]);
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
        return PlanCatalog::planOptions();
    }

    public static function subscriptionStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'demo' => 'Demo',
            'trial' => 'Trial',
            'expired' => 'Expired',
            'suspended' => 'Suspended',
        ];
    }

    public static function suspensionCategoryOptions(): array
    {
        return [
            'billing_issue' => 'Billing issue',
            'abuse_security' => 'Abuse / security',
            'manual_review' => 'Manual review',
            'client_request' => 'Client request',
        ];
    }

    public static function billingIntervalOptions(): array
    {
        return [
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            'manual' => 'Manual / offline',
        ];
    }

    public static function billingProviderOptions(): array
    {
        return [
            'manual' => 'Manual / offline',
            'stripe' => 'Stripe',
            'bank_transfer' => 'Bank transfer',
            'other' => 'Other',
        ];
    }

    public static function demoResetFrequencyOptions(): array
    {
        return [
            'manual' => 'Manual reset only',
            'daily' => 'Reset daily',
            'weekly' => 'Reset weekly',
        ];
    }

    public static function isDemoWorkspace(?Workspace $workspace): bool
    {
        return $workspace !== null
            && ($workspace->plan === 'demo' || $workspace->subscription_status === 'demo');
    }

    public static function subscriptionStatusColor(string $state): string
    {
        return match ($state) {
            'expired', 'suspended' => 'danger',
            'trial', 'demo' => 'warning',
            default => 'success',
        };
    }

    public static function accessStateLabel(Workspace $workspace): string
    {
        if ($workspace->is_suspended || $workspace->subscription_status === 'suspended') {
            return 'Suspended';
        }

        if (filled($workspace->access_override_reason) && (
            $workspace->access_override_ends_at === null || $workspace->access_override_ends_at->isFuture()
        )) {
            return 'Manual access';
        }

        if ($workspace->plan === 'demo' || $workspace->subscription_status === 'demo') {
            return 'Demo workspace';
        }

        if ($workspace->subscription_status === 'expired' || $workspace->subscription_ends_at?->isPast()) {
            return 'Expired / read-only';
        }

        if ($workspace->subscription_status === 'trial' && $workspace->trial_ends_at?->between(now(), now()->addDays(7))) {
            return 'Trial ending soon';
        }

        return 'Active';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformWorkspaces::route('/'),
            'view' => ViewPlatformWorkspace::route('/{record}'),
            'edit' => EditPlatformWorkspace::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SubscriptionEventsRelationManager::class,
            WorkspaceNotesRelationManager::class,
        ];
    }
}
