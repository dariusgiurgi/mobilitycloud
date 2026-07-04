<?php

namespace App\Filament\Resources\PlatformSubscriptions;

use App\Filament\Resources\PlatformSubscriptions\Pages\ListPlatformSubscriptions;
use App\Filament\Resources\PlatformWorkspaces\PlatformWorkspaceResource;
use App\Models\PlatformSubscriptionEvent;
use App\Models\Workspace;
use App\Services\DemoWorkspaceResetService;
use App\Support\PlanCatalog;
use App\Support\PlatformAudit;
use App\Support\PlatformSubscriptionTimeline;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformSubscriptionResource extends Resource
{
    protected static ?string $model = Workspace::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & access';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'subscription';

    protected static ?string $pluralModelLabel = 'subscriptions';

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
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['latestSubscriptionEvent.actor'])
                ->withCount(['users', 'projects']))
            ->columns([
                TextColumn::make('name')
                    ->label('Workspace')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Workspace $record): string => $record->owner()?->email ?? 'No owner account'),
                TextColumn::make('access_state')
                    ->label('Access')
                    ->badge()
                    ->state(fn (Workspace $record): string => PlatformWorkspaceResource::accessStateLabel($record))
                    ->color(fn (Workspace $record): string => match (PlatformWorkspaceResource::accessStateLabel($record)) {
                        'Suspended', 'Expired / read-only' => 'danger',
                        'Manual access', 'Trial ending soon', 'Demo workspace' => 'warning',
                        default => 'success',
                    })
                    ->description(fn (Workspace $record): ?string => $record->is_suspended
                        ? ($record->suspension_category ? (PlatformWorkspaceResource::suspensionCategoryOptions()[$record->suspension_category] ?? $record->suspension_category) : null)
                        : null),
                TextColumn::make('next_action')
                    ->label('Next action')
                    ->state(fn (Workspace $record): string => self::nextActionLabel($record))
                    ->badge()
                    ->color(fn (Workspace $record): string => self::nextActionColor($record))
                    ->description(fn (Workspace $record): ?string => self::nextActionDescription($record))
                    ->wrap(),
                TextColumn::make('latest_subscription_event')
                    ->label('Last event')
                    ->state(fn (Workspace $record): string => self::latestEventLabel($record))
                    ->badge()
                    ->color(fn (Workspace $record): string => self::latestEventColor($record->latestSubscriptionEvent))
                    ->description(fn (Workspace $record): ?string => self::latestEventDescription($record))
                    ->placeholder('No events yet')
                    ->wrap(),
                TextColumn::make('plan')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PlatformWorkspaceResource::planOptions()[$state ?: 'free'] ?? ($state ?: 'Free'))
                    ->color(fn (?string $state): string => match ($state) {
                        'demo' => 'warning',
                        'writer_pro' => 'success',
                        'writer' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('subscription_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PlatformWorkspaceResource::subscriptionStatusOptions()[$state ?: 'active'] ?? ucfirst((string) $state))
                    ->color(fn (?string $state): string => PlatformWorkspaceResource::subscriptionStatusColor($state ?: 'active')),
                TextColumn::make('trial_ends_at')
                    ->label('Trial ends')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('subscription_ends_at')
                    ->label('Subscription ends')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('billing_amount')
                    ->label('Billing')
                    ->state(fn (Workspace $record): string => $record->billing_amount
                        ? ($record->billing_currency ?: 'EUR').' '.number_format((float) $record->billing_amount, 2).($record->billing_interval ? ' / '.$record->billing_interval : '')
                        : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('billing_provider')
                    ->label('Provider')
                    ->formatStateUsing(fn (?string $state): string => $state ? (PlatformWorkspaceResource::billingProviderOptions()[$state] ?? str($state)->replace('_', ' ')->title()) : '—')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('access_override_ends_at')
                    ->label('Manual access')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('demo_last_reset_at')
                    ->label('Demo reset')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Never')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('projects_count')
                    ->label('Projects')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->options(PlatformWorkspaceResource::planOptions()),
                SelectFilter::make('subscription_status')
                    ->options(PlatformWorkspaceResource::subscriptionStatusOptions()),
                Filter::make('needs_attention')
                    ->label('Needs attention')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                        $query
                            ->where('is_suspended', true)
                            ->orWhere('subscription_status', 'expired')
                            ->orWhere('subscription_status', 'suspended')
                            ->orWhere(function (Builder $query): void {
                                $query
                                    ->whereNotNull('subscription_ends_at')
                                    ->where('subscription_ends_at', '<=', now()->addDays(14));
                            })
                            ->orWhere(function (Builder $query): void {
                                $query
                                    ->where('subscription_status', 'trial')
                                    ->whereNotNull('trial_ends_at')
                                    ->where('trial_ends_at', '<=', now()->addDays(7));
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
                Filter::make('demo')
                    ->label('Demo')
                    ->query(fn (Builder $query): Builder => $query
                        ->where(fn (Builder $query): Builder => $query
                            ->where('plan', 'demo')
                            ->orWhere('subscription_status', 'demo'))),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewSubscription')
                        ->label('View details')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->modalHeading(fn (Workspace $record): string => 'Subscription details · '.$record->name)
                        ->modalContent(fn (Workspace $record) => view('filament.modals.platform-workspace-subscription', [
                            'record' => $record->loadMissing(['accessOverrideGrantor', 'suspendedBy'])->loadCount(['users', 'projects']),
                            'planOptions' => PlatformWorkspaceResource::planOptions(),
                            'moduleOptions' => PlanCatalog::moduleOptions(),
                            'suspensionCategoryOptions' => PlatformWorkspaceResource::suspensionCategoryOptions(),
                            'accessState' => PlatformWorkspaceResource::accessStateLabel($record),
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
                    Action::make('editBilling')
                        ->label('Edit billing')
                        ->icon('heroicon-o-credit-card')
                        ->color('info')
                        ->modalHeading(fn (Workspace $record): string => 'Billing readiness · '.$record->name)
                        ->modalDescription('Internal commercial metadata only. Saving this does not trigger payments or contact a billing provider.')
                        ->fillForm(fn (Workspace $record): array => [
                            'billing_interval' => $record->billing_interval,
                            'billing_amount' => $record->billing_amount,
                            'billing_currency' => $record->billing_currency ?: 'EUR',
                            'billing_reference' => $record->billing_reference,
                            'billing_provider' => $record->billing_provider,
                            'billing_provider_customer_id' => $record->billing_provider_customer_id,
                            'billing_provider_subscription_id' => $record->billing_provider_subscription_id,
                        ])
                        ->form([
                            Select::make('billing_interval')
                                ->label('Billing interval')
                                ->options(PlatformWorkspaceResource::billingIntervalOptions())
                                ->native(false)
                                ->placeholder('Not set'),
                            TextInput::make('billing_amount')
                                ->label('Amount')
                                ->numeric()
                                ->minValue(0),
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
                                ->options(PlatformWorkspaceResource::billingProviderOptions())
                                ->native(false)
                                ->placeholder('Not connected'),
                            TextInput::make('billing_provider_customer_id')
                                ->label('Provider customer ID')
                                ->maxLength(255),
                            TextInput::make('billing_provider_subscription_id')
                                ->label('Provider subscription ID')
                                ->maxLength(255),
                        ])
                        ->action(function (Workspace $record, array $data): void {
                            $record->update([
                                'billing_interval' => $data['billing_interval'] ?? null,
                                'billing_amount' => $data['billing_amount'] ?? null,
                                'billing_currency' => $data['billing_currency'] ?? null,
                                'billing_reference' => $data['billing_reference'] ?? null,
                                'billing_provider' => $data['billing_provider'] ?? null,
                                'billing_provider_customer_id' => $data['billing_provider_customer_id'] ?? null,
                                'billing_provider_subscription_id' => $data['billing_provider_subscription_id'] ?? null,
                            ]);

                            PlatformSubscriptionTimeline::record($record, 'billing_updated', 'Billing readiness details updated.', [
                                'billing_interval' => $record->billing_interval,
                                'billing_amount' => $record->billing_amount,
                                'billing_currency' => $record->billing_currency,
                                'billing_provider' => $record->billing_provider,
                                'billing_reference' => $record->billing_reference,
                            ]);
                            PlatformAudit::log('workspace.billing_updated', 'Updated billing readiness for '.$record->name, $record);
                            Notification::make()->title('Billing details updated')->success()->send();
                        }),
                    Action::make('setTrialPeriod')
                        ->label('Set trial period')
                        ->icon('heroicon-o-calendar-days')
                        ->fillForm(fn (Workspace $record): array => [
                            'trial_ends_at' => $record->trial_ends_at ?: now()->addDays(14),
                        ])
                        ->form([
                            DateTimePicker::make('trial_ends_at')
                                ->label('Trial ends')
                                ->required()
                                ->minDate(now()->subDay()),
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
                                ->options(PlatformWorkspaceResource::planOptions())
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
                                ->options(PlatformWorkspaceResource::demoResetFrequencyOptions())
                                ->default('manual')
                                ->required()
                                ->native(false),
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
                        ->visible(fn (Workspace $record): bool => (auth()->user()?->canManagePlatformAdmins() ?? false) && PlatformWorkspaceResource::isDemoWorkspace($record))
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
                    Action::make('suspend')
                        ->label('Suspend')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->modalHeading(fn (Workspace $record): string => 'Suspend '.$record->name.'?')
                        ->modalDescription('Suspended workspaces lose access to client modules until reactivated or granted manual access. Existing project data is preserved, but normal users are blocked from using the workspace.')
                        ->form([
                            Select::make('suspension_category')
                                ->label('Reason category')
                                ->options(PlatformWorkspaceResource::suspensionCategoryOptions())
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
                                ->helperText('This prevents accidental access blocking from the subscriptions table.'),
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

                            PlatformSubscriptionTimeline::record($record, 'suspended', 'Workspace suspended: '.(PlatformWorkspaceResource::suspensionCategoryOptions()[$data['suspension_category']] ?? $data['suspension_category']).'.', [
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
                    Action::make('editWorkspace')
                        ->label('Open workspace record')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Workspace $record): string => PlatformWorkspaceResource::getUrl('edit', ['record' => $record], panel: 'platform')),
                ]),
            ])
            ->defaultSort('subscription_ends_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformSubscriptions::route('/'),
        ];
    }

    public static function nextActionLabel(Workspace $workspace): string
    {
        if ($workspace->is_suspended || $workspace->subscription_status === 'suspended') {
            return 'Review suspension';
        }

        if ($workspace->subscription_status === 'expired' || $workspace->subscription_ends_at?->isPast()) {
            return 'Renew or activate';
        }

        if ($workspace->subscription_status === 'trial') {
            if ($workspace->trial_ends_at?->isPast()) {
                return 'Trial expired';
            }

            if ($workspace->trial_ends_at?->between(now(), now()->addDays(7))) {
                return 'Extend or convert';
            }
        }

        if (PlatformWorkspaceResource::isDemoWorkspace($workspace)) {
            return 'Reset when needed';
        }

        if (filled($workspace->access_override_reason)) {
            if ($workspace->access_override_ends_at === null) {
                return 'Review manual access';
            }

            if ($workspace->access_override_ends_at->isPast()) {
                return 'Manual access expired';
            }

            if ($workspace->access_override_ends_at->between(now(), now()->addDays(14))) {
                return 'Manual access ending';
            }
        }

        if ($workspace->subscription_ends_at?->between(now(), now()->addDays(14))) {
            return 'Renew soon';
        }

        return 'No action needed';
    }

    public static function nextActionColor(Workspace $workspace): string
    {
        return match (self::nextActionLabel($workspace)) {
            'Review suspension', 'Renew or activate', 'Trial expired', 'Manual access expired' => 'danger',
            'Extend or convert', 'Reset when needed', 'Review manual access', 'Manual access ending', 'Renew soon' => 'warning',
            default => 'success',
        };
    }

    public static function nextActionDescription(Workspace $workspace): ?string
    {
        return match (self::nextActionLabel($workspace)) {
            'Review suspension' => $workspace->suspension_reason ?: 'Access is blocked until reactivated.',
            'Renew or activate' => 'Expired access should be renewed, activated or granted manually.',
            'Trial expired' => 'Trial ended '.$workspace->trial_ends_at?->diffForHumans().'.',
            'Extend or convert' => 'Trial ends '.$workspace->trial_ends_at?->diffForHumans().'.',
            'Reset when needed' => 'Demo reset: '.(PlatformWorkspaceResource::demoResetFrequencyOptions()[$workspace->demo_reset_frequency ?: 'manual'] ?? 'Manual reset only').'.',
            'Review manual access' => 'Owner-granted access has no end date.',
            'Manual access expired' => 'Manual access ended '.$workspace->access_override_ends_at?->diffForHumans().'.',
            'Manual access ending' => 'Manual access ends '.$workspace->access_override_ends_at?->diffForHumans().'.',
            'Renew soon' => 'Subscription ends '.$workspace->subscription_ends_at?->diffForHumans().'.',
            default => null,
        };
    }

    public static function latestEventLabel(Workspace $workspace): string
    {
        $event = $workspace->latestSubscriptionEvent;

        if (! $event) {
            return 'No events yet';
        }

        $label = PlatformSubscriptionEvent::typeOptions()[$event->event_type] ?? str($event->event_type)->replace('_', ' ')->title();

        return $label.' · '.$event->created_at?->diffForHumans();
    }

    public static function latestEventDescription(Workspace $workspace): ?string
    {
        $event = $workspace->latestSubscriptionEvent;

        if (! $event) {
            return null;
        }

        $actor = $event->actor?->name ?: 'System';

        return $actor.' · '.$event->summary;
    }

    public static function latestEventColor(?PlatformSubscriptionEvent $event): string
    {
        return match ($event?->event_type) {
            'expired', 'suspended' => 'danger',
            'trial_extended', 'trial_updated', 'demo_enabled', 'demo_reset', 'demo_reset_configured' => 'warning',
            'activated', 'reactivated', 'manual_access_granted' => 'success',
            'plan_changed', 'status_changed', 'billing_updated' => 'info',
            default => 'gray',
        };
    }
}
