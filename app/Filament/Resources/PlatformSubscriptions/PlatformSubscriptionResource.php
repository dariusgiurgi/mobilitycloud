<?php

namespace App\Filament\Resources\PlatformSubscriptions;

use App\Filament\Resources\PlatformSubscriptions\Pages\ListPlatformSubscriptions;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Models\PlatformSubscriptionEvent;
use App\Models\User;
use App\Support\AccountAccess;
use App\Support\PlanCatalog;
use App\Support\PlatformAccountNotificationAction;
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
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & access';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'account subscription';

    protected static ?string $pluralModelLabel = 'account subscriptions';

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
                ->where('role', User::ROLE_USER)
                ->with(['latestSubscriptionEvent.actor', 'accessOverrideGrantor', 'suspendedBy'])
                ->withCount(['ownedProjects', 'projects']))
            ->columns([
                TextColumn::make('name')
                    ->label('Account')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->email),
                TextColumn::make('access_state')
                    ->label('Access')
                    ->badge()
                    ->state(fn (User $record): string => self::accessStateLabel($record))
                    ->color(fn (User $record): string => match (self::accessStateLabel($record)) {
                        'Suspended', 'Expired / read-only' => 'danger',
                        'Manual access', 'Trial ending soon', 'Demo account' => 'warning',
                        default => 'success',
                    })
                    ->description(fn (User $record): ?string => $record->is_suspended
                        ? ($record->suspension_category ? (self::suspensionCategoryOptions()[$record->suspension_category] ?? $record->suspension_category) : null)
                        : null),
                TextColumn::make('next_action')
                    ->label('Next action')
                    ->state(fn (User $record): string => self::nextActionLabel($record))
                    ->badge()
                    ->color(fn (User $record): string => self::nextActionColor($record))
                    ->description(fn (User $record): ?string => self::nextActionDescription($record))
                    ->wrap(),
                TextColumn::make('latest_subscription_event')
                    ->label('Last event')
                    ->state(fn (User $record): string => self::latestEventLabel($record))
                    ->badge()
                    ->color(fn (User $record): string => self::latestEventColor($record->latestSubscriptionEvent))
                    ->description(fn (User $record): ?string => self::latestEventDescription($record))
                    ->placeholder('No events yet')
                    ->wrap(),
                TextColumn::make('plan')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::planOptions()[$state ?: 'free'] ?? ($state ?: 'Free'))
                    ->color(fn (?string $state): string => match ($state) {
                        'demo' => 'warning',
                        'writer_pro' => 'success',
                        'writer' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('subscription_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::subscriptionStatusOptions()[$state ?: 'active'] ?? ucfirst((string) $state))
                    ->color(fn (?string $state): string => self::subscriptionStatusColor($state ?: 'active')),
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
                    ->state(fn (User $record): string => $record->billing_amount
                        ? ($record->billing_currency ?: 'EUR').' '.number_format((float) $record->billing_amount, 2).($record->billing_interval ? ' / '.$record->billing_interval : '')
                        : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('billing_provider')
                    ->label('Provider')
                    ->formatStateUsing(fn (?string $state): string => $state ? (self::billingProviderOptions()[$state] ?? str($state)->replace('_', ' ')->title()) : '—')
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
                TextColumn::make('owned_projects_count')
                    ->label('Owned projects')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('projects_count')
                    ->label('Shared projects')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->options(self::planOptions()),
                SelectFilter::make('subscription_status')
                    ->options(self::subscriptionStatusOptions()),
                Filter::make('needs_attention')
                    ->label('Needs attention')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                        $query
                            ->where('is_suspended', true)
                            ->orWhereIn('subscription_status', ['expired', 'suspended'])
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
                        ->modalHeading(fn (User $record): string => 'Subscription details · '.$record->name)
                        ->modalContent(fn (User $record) => view('filament.modals.platform-account-subscription', [
                            'record' => $record->loadMissing(['latestSubscriptionEvent.actor', 'accessOverrideGrantor', 'suspendedBy'])->loadCount(['ownedProjects', 'projects']),
                            'planOptions' => self::planOptions(),
                            'moduleOptions' => PlanCatalog::moduleOptions(),
                            'suspensionCategoryOptions' => self::suspensionCategoryOptions(),
                            'accessState' => self::accessStateLabel($record),
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
                    PlatformAccountNotificationAction::make(),
                    Action::make('editBilling')
                        ->label('Edit billing')
                        ->icon('heroicon-o-credit-card')
                        ->color('info')
                        ->modalHeading(fn (User $record): string => 'Billing readiness · '.$record->name)
                        ->modalDescription('Internal commercial metadata only. Saving this does not trigger payments or contact a billing provider.')
                        ->fillForm(fn (User $record): array => [
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
                                ->options(self::billingIntervalOptions())
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
                                ->options(self::billingProviderOptions())
                                ->native(false)
                                ->placeholder('Not connected'),
                            TextInput::make('billing_provider_customer_id')
                                ->label('Provider customer ID')
                                ->maxLength(255),
                            TextInput::make('billing_provider_subscription_id')
                                ->label('Provider subscription ID')
                                ->maxLength(255),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update([
                                'billing_interval' => $data['billing_interval'] ?? null,
                                'billing_amount' => $data['billing_amount'] ?? null,
                                'billing_currency' => $data['billing_currency'] ?? null,
                                'billing_reference' => $data['billing_reference'] ?? null,
                                'billing_provider' => $data['billing_provider'] ?? null,
                                'billing_provider_customer_id' => $data['billing_provider_customer_id'] ?? null,
                                'billing_provider_subscription_id' => $data['billing_provider_subscription_id'] ?? null,
                            ]);

                            PlatformSubscriptionTimeline::recordAccount($record, 'billing_updated', 'Billing readiness details updated.', [
                                'billing_interval' => $record->billing_interval,
                                'billing_amount' => $record->billing_amount,
                                'billing_currency' => $record->billing_currency,
                                'billing_provider' => $record->billing_provider,
                                'billing_reference' => $record->billing_reference,
                            ]);
                            PlatformAudit::log('account.billing_updated', 'Updated billing readiness for '.$record->email, $record);
                            Notification::make()->title('Billing details updated')->success()->send();
                        }),
                    Action::make('setTrialPeriod')
                        ->label('Set trial period')
                        ->icon('heroicon-o-calendar-days')
                        ->fillForm(fn (User $record): array => [
                            'trial_ends_at' => $record->trial_ends_at ?: now()->addDays(14),
                        ])
                        ->form([
                            DateTimePicker::make('trial_ends_at')
                                ->label('Trial ends')
                                ->required()
                                ->minDate(now()->subDay()),
                        ])
                        ->action(function (User $record, array $data): void {
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

                            PlatformSubscriptionTimeline::recordAccount($record, 'trial_updated', 'Trial period updated.', [
                                'trial_ends_at' => $record->trial_ends_at?->toISOString(),
                                'unblocked' => true,
                            ]);
                            PlatformAudit::log('account.trial_updated', 'Updated trial period for '.$record->email, $record);
                            Notification::make()->title('Trial period updated')->success()->send();
                        }),
                    Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (User $record): void {
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

                            PlatformSubscriptionTimeline::recordAccount($record, 'activated', 'Account activated and access restored.', [
                                'unblocked' => true,
                            ]);
                            PlatformAudit::log('account.activated', 'Activated account '.$record->email, $record);
                            Notification::make()->title('Account activated')->success()->send();
                        }),
                    Action::make('grantManualAccess')
                        ->label('Owner only · Grant manual access')
                        ->icon('heroicon-o-key')
                        ->color('info')
                        ->visible(fn (): bool => auth()->user()?->canManagePlatformAdmins() ?? false)
                        ->modalHeading(fn (User $record): string => 'Grant manual access · '.$record->email)
                        ->modalDescription('Owner-only override. This bypasses normal billing restrictions for support, partner access or commercial exceptions, so include a clear reason and an end date whenever possible.')
                        ->fillForm(fn (User $record): array => [
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
                        ->action(function (User $record, array $data): void {
                            $record->update([
                                ...PlanCatalog::accountDefaults((string) $data['plan']),
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

                            PlatformSubscriptionTimeline::recordAccount($record, 'manual_access_granted', 'Owner granted manual access.', [
                                'plan' => $record->plan,
                                'access_override_ends_at' => $record->access_override_ends_at?->toISOString(),
                                'reason' => $record->access_override_reason,
                            ]);
                            PlatformAudit::log('account.manual_access_granted', 'Granted manual access for '.$record->email, $record);
                            Notification::make()->title('Manual access granted')->success()->send();
                        }),
                    Action::make('markDemo')
                        ->label('Owner only · Mark as demo')
                        ->icon('heroicon-o-beaker')
                        ->color('warning')
                        ->visible(fn (): bool => auth()->user()?->canManagePlatformAdmins() ?? false)
                        ->modalHeading(fn (User $record): string => 'Turn '.$record->email.' into a demo account?')
                        ->modalDescription('Demo accounts are for testing and presentations. They get all modules, demo limits and are marked as internal/test. Real client accounts should not be converted unless this is intentional.')
                        ->form([
                            Textarea::make('support_notes')
                                ->label('Demo note')
                                ->default('Demo account for testing or product presentation.')
                                ->rows(3)
                                ->maxLength(3000),
                            Select::make('demo_reset_frequency')
                                ->label('Reset frequency')
                                ->options(self::demoResetFrequencyOptions())
                                ->default('manual')
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update([
                                'plan' => 'demo',
                                'subscription_status' => 'demo',
                                'feature_flags' => PlanCatalog::defaultModules('demo'),
                                'plan_limits' => PlanCatalog::defaultLimits('demo'),
                                'trial_ends_at' => null,
                                'subscription_ends_at' => null,
                                'is_suspended' => false,
                                'demo_reset_frequency' => $data['demo_reset_frequency'] ?? 'manual',
                                'support_notes' => $data['support_notes'] ?: 'Demo account for testing or product presentation.',
                                'access_override_ends_at' => null,
                                'access_override_reason' => null,
                                'access_override_granted_by' => null,
                                'suspension_category' => null,
                                'suspension_reason' => null,
                                'suspended_at' => null,
                                'suspended_by' => null,
                            ]);

                            PlatformSubscriptionTimeline::recordAccount($record, 'demo_enabled', 'Account marked as demo.', [
                                'plan' => 'demo',
                                'demo_reset_frequency' => $record->demo_reset_frequency,
                                'limits' => PlanCatalog::defaultLimits('demo'),
                            ]);
                            PlatformAudit::log('account.demo_enabled', 'Marked account '.$record->email.' as demo', $record);
                            Notification::make()->title('Demo account enabled')->success()->send();
                        }),
                    Action::make('suspend')
                        ->label('Suspend')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->modalHeading(fn (User $record): string => 'Suspend '.$record->email.'?')
                        ->modalDescription('Suspended accounts lose access to paid modules until reactivated or granted manual access. Existing project data is preserved.')
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
                                ->helperText('This prevents accidental access blocking from the subscriptions table.'),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update([
                                'subscription_status' => 'suspended',
                                'is_suspended' => true,
                                'suspension_category' => $data['suspension_category'],
                                'suspension_reason' => $data['suspension_reason'],
                                'suspended_at' => now(),
                                'suspended_by' => auth()->id(),
                            ]);

                            PlatformSubscriptionTimeline::recordAccount($record, 'suspended', 'Account suspended: '.(self::suspensionCategoryOptions()[$data['suspension_category']] ?? $data['suspension_category']).'.', [
                                'category' => $data['suspension_category'],
                                'reason' => $data['suspension_reason'],
                            ]);
                            PlatformAudit::log('account.suspended', 'Suspended account '.$record->email, $record, [
                                'category' => $data['suspension_category'],
                            ]);
                            Notification::make()->title('Account suspended')->success()->send();
                        }),
                    Action::make('expire')
                        ->label('Expire')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->modalHeading(fn (User $record): string => 'Mark '.$record->email.' as expired?')
                        ->modalDescription('This immediately marks the subscription as expired and sets the end date to now. Use this for billing corrections or ended contracts, not for temporary support holds.')
                        ->form([
                            TextInput::make('confirmation')
                                ->label('Type EXPIRE to confirm')
                                ->required()
                                ->rule('in:EXPIRE'),
                        ])
                        ->action(function (User $record): void {
                            $record->update(['subscription_status' => 'expired', 'subscription_ends_at' => now()]);
                            PlatformSubscriptionTimeline::recordAccount($record, 'expired', 'Account marked as expired.', [
                                'subscription_ends_at' => $record->subscription_ends_at?->toISOString(),
                            ]);
                            PlatformAudit::log('account.expired', 'Expired account '.$record->email, $record);
                            Notification::make()->title('Account expired')->success()->send();
                        }),
                    Action::make('editAccount')
                        ->label('Open account record')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (User $record): string => PlatformUserResource::getUrl('edit', ['record' => $record], panel: 'platform')),
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
        return PlatformUserResource::suspensionCategoryOptions();
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

    public static function isDemoAccount(?User $account): bool
    {
        return $account !== null
            && ($account->plan === 'demo' || $account->subscription_status === 'demo');
    }

    public static function subscriptionStatusColor(string $state): string
    {
        return match ($state) {
            'expired', 'suspended' => 'danger',
            'trial', 'demo' => 'warning',
            default => 'success',
        };
    }

    public static function accessStateLabel(User $account): string
    {
        if ($account->is_suspended || $account->subscription_status === 'suspended') {
            return 'Suspended';
        }

        if (AccountAccess::hasOwnerGrantedAccess($account)) {
            return 'Manual access';
        }

        if (self::isDemoAccount($account)) {
            return 'Demo account';
        }

        if (AccountAccess::isReadOnly($account)) {
            return 'Expired / read-only';
        }

        if ($account->subscription_status === 'trial' && $account->trial_ends_at?->between(now(), now()->addDays(7))) {
            return 'Trial ending soon';
        }

        return 'Active';
    }

    public static function nextActionLabel(User $account): string
    {
        if ($account->is_suspended || $account->subscription_status === 'suspended') {
            return 'Review suspension';
        }

        if ($account->subscription_status === 'expired' || $account->subscription_ends_at?->isPast()) {
            return 'Renew or activate';
        }

        if ($account->subscription_status === 'trial') {
            if ($account->trial_ends_at?->isPast()) {
                return 'Trial expired';
            }

            if ($account->trial_ends_at?->between(now(), now()->addDays(7))) {
                return 'Extend or convert';
            }
        }

        if (self::isDemoAccount($account)) {
            return 'Reset when needed';
        }

        if (filled($account->access_override_reason)) {
            if ($account->access_override_ends_at === null) {
                return 'Review manual access';
            }

            if ($account->access_override_ends_at->isPast()) {
                return 'Manual access expired';
            }

            if ($account->access_override_ends_at->between(now(), now()->addDays(14))) {
                return 'Manual access ending';
            }
        }

        if ($account->subscription_ends_at?->between(now(), now()->addDays(14))) {
            return 'Renew soon';
        }

        return 'No action needed';
    }

    public static function nextActionColor(User $account): string
    {
        return match (self::nextActionLabel($account)) {
            'Review suspension', 'Renew or activate', 'Trial expired', 'Manual access expired' => 'danger',
            'Extend or convert', 'Reset when needed', 'Review manual access', 'Manual access ending', 'Renew soon' => 'warning',
            default => 'success',
        };
    }

    public static function nextActionDescription(User $account): ?string
    {
        return match (self::nextActionLabel($account)) {
            'Review suspension' => $account->suspension_reason ?: 'Access is blocked until reactivated.',
            'Renew or activate' => 'Expired access should be renewed, activated or granted manually.',
            'Trial expired' => 'Trial ended '.$account->trial_ends_at?->diffForHumans().'.',
            'Extend or convert' => 'Trial ends '.$account->trial_ends_at?->diffForHumans().'.',
            'Reset when needed' => 'Demo reset: '.(self::demoResetFrequencyOptions()[$account->demo_reset_frequency ?: 'manual'] ?? 'Manual reset only').'.',
            'Review manual access' => 'Owner-granted access has no end date.',
            'Manual access expired' => 'Manual access ended '.$account->access_override_ends_at?->diffForHumans().'.',
            'Manual access ending' => 'Manual access ends '.$account->access_override_ends_at?->diffForHumans().'.',
            'Renew soon' => 'Subscription ends '.$account->subscription_ends_at?->diffForHumans().'.',
            default => null,
        };
    }

    public static function latestEventLabel(User $account): string
    {
        $event = $account->latestSubscriptionEvent;

        if (! $event) {
            return 'No events yet';
        }

        $label = PlatformSubscriptionEvent::typeOptions()[$event->event_type] ?? str($event->event_type)->replace('_', ' ')->title();

        return $label.' · '.$event->created_at?->diffForHumans();
    }

    public static function latestEventDescription(User $account): ?string
    {
        $event = $account->latestSubscriptionEvent;

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
