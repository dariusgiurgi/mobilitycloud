<?php

namespace App\Filament\Resources\PlatformUsers;

use App\Filament\Resources\PlatformUsers\Pages\CreatePlatformUser;
use App\Filament\Resources\PlatformUsers\Pages\EditPlatformUser;
use App\Filament\Resources\PlatformUsers\Pages\ListPlatformUsers;
use App\Filament\Resources\PlatformUsers\Pages\ViewPlatformUser;
use App\Filament\Resources\PlatformUsers\RelationManagers\SupportNotesRelationManager;
use App\Jobs\DeletePlatformAccount;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Support\PlanCatalog;
use App\Support\PlatformAccountNotificationAction;
use App\Support\PlatformAudit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class PlatformUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform management';

    protected static ?string $navigationLabel = 'Accounts';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'account';

    protected static ?string $pluralModelLabel = 'accounts';

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
        return auth()->user()?->canManagePlatformAdmins() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof User && static::canManageAccount($record);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof User && (auth()->user()?->isPlatformAdmin() ?? false);
    }

    public static function canManageAccount(User $record): bool
    {
        $actor = auth()->user();

        if (! $actor?->isPlatformAdmin()) {
            return false;
        }

        if (filled($record->account_deletion_status)) {
            return false;
        }

        if ($actor->canManagePlatformAdmins()) {
            return true;
        }

        return ! $record->isPlatformAdmin();
    }

    public static function canPermanentlyDeleteAccount(User $record): bool
    {
        $actor = auth()->user();

        if (! $actor?->canManagePlatformAdmins() || filled($actor->archived_at) || $actor->is_suspended) {
            return false;
        }

        if (filled($record->account_deletion_status)) {
            return false;
        }

        if ($record->is($actor)) {
            return false;
        }

        if ($record->isPlatformOwner()) {
            return User::query()
                ->whereKeyNot($record->id)
                ->whereNull('archived_at')
                ->where('is_suspended', false)
                ->whereNull('account_deletion_status')
                ->whereIn('role', [User::ROLE_PLATFORM_OWNER, User::ROLE_ADMIN])
                ->exists();
        }

        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->description('Global account data. Project access is managed on each project.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->live(onBlur: true)
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->visible(fn (string $operation): bool => $operation === 'create')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->columnSpanFull(),
                    Select::make('role')
                        ->label('Global role')
                        ->options([
                            User::ROLE_USER => 'User',
                            ...User::platformRoleOptions(),
                        ])
                        ->default(User::ROLE_USER)
                        ->required()
                        ->live()
                        ->disabled(fn (): bool => ! (auth()->user()?->canManagePlatformAdmins() ?? false))
                        ->dehydrated(fn (): bool => auth()->user()?->canManagePlatformAdmins() ?? false)
                        ->helperText('Only platform owners can promote or demote platform staff.'),
                    Toggle::make('is_suspended')
                        ->label('Suspended')
                        ->helperText('Suspended accounts are marked for support review.')
                        ->live()
                        ->inline(false),
                    Select::make('suspension_category')
                        ->label('Suspension category')
                        ->options(self::suspensionCategoryOptions())
                        ->native(false)
                        ->live()
                        ->required(fn (callable $get): bool => (bool) $get('is_suspended'))
                        ->visible(fn (callable $get): bool => (bool) $get('is_suspended')),
                    Textarea::make('suspension_reason')
                        ->label('Suspension reason')
                        ->rows(4)
                        ->live(onBlur: true)
                        ->maxLength(2000)
                        ->columnSpanFull()
                        ->required(fn (callable $get): bool => (bool) $get('is_suspended'))
                        ->visible(fn (callable $get): bool => (bool) $get('is_suspended')),
                    Toggle::make('must_change_password')
                        ->label('Require password change')
                        ->live()
                        ->inline(false),
                    Textarea::make('support_notes')
                        ->label('Internal support notes')
                        ->rows(5)
                        ->live(onBlur: true)
                        ->maxLength(3000)
                        ->columnSpanFull(),
                    Select::make('plan')
                        ->label('Account access')
                        ->options(PlanCatalog::planOptions())
                        ->default('standard')
                        ->live()
                        ->required(),
                    Select::make('subscription_status')
                        ->label('Access status')
                        ->options([
                            'active' => 'Active',
                            'expired' => 'Expired',
                            'suspended' => 'Suspended',
                        ])
                        ->default('active')
                        ->live()
                        ->required(),
                    TextInput::make('billing_name')
                        ->label('Billing name')
                        ->live(onBlur: true)
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('billing_vat')
                        ->label('VAT / registration')
                        ->live(onBlur: true)
                        ->maxLength(255),
                    TextInput::make('billing_country')
                        ->label('Billing country')
                        ->live(onBlur: true)
                        ->maxLength(255),
                    Textarea::make('billing_address')
                        ->label('Billing address')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Account overview')
                    ->description('Read-only account summary before taking support or security actions.')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable(),
                        TextEntry::make('platform_role_label')
                            ->label('Role')
                            ->badge()
                            ->color(fn (User $record): string => match ($record->role) {
                                User::ROLE_PLATFORM_OWNER => 'danger',
                                User::ROLE_PLATFORM_ADMIN => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('account_status')
                            ->label('Status')
                            ->state(fn (User $record): string => self::accountStatusLabel($record))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Archived' => 'gray',
                                'Suspended' => 'danger',
                                'Password change required' => 'warning',
                                'Verification pending' => 'warning',
                                default => 'success',
                            }),
                        TextEntry::make('plan')
                            ->label('Access')
                            ->formatStateUsing(fn (?string $state): string => PlanCatalog::displayPlanLabel($state)),
                        TextEntry::make('billing_identity')
                            ->label('Billing')
                            ->state(fn (User $record): string => $record->isUnlimitedAccount()
                                ? 'Not required for unlimited'
                                : ($record->hasBillingDetails() ? $record->billing_name : 'Missing billing details'))
                            ->badge()
                            ->color(fn (User $record): string => $record->isUnlimitedAccount() || $record->hasBillingDetails() ? 'success' : 'danger'),
                        TextEntry::make('owned_projects_count')
                            ->label('Owned projects')
                            ->state(fn (User $record): int => $record->ownedProjects()->count()),
                        TextEntry::make('last_login_at')
                            ->label('Last login')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),
                    ]),
                Section::make('Operational context')
                    ->description('Flags that explain why an account may need attention.')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('suspension_category')
                            ->label('Suspension category')
                            ->formatStateUsing(fn (?string $state): string => $state ? (self::suspensionCategoryOptions()[$state] ?? $state) : '—'),
                        TextEntry::make('suspension_reason')
                            ->label('Suspension reason')
                            ->placeholder('—')
                            ->prose(),
                        TextEntry::make('archived_at')
                            ->label('Archived at')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),
                        TextEntry::make('archived_reason')
                            ->label('Archive reason')
                            ->placeholder('—')
                            ->prose(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withTrashed()->withCount(['ownedProjects', 'projects']))
            ->columns([
                TextColumn::make('name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->email),
                TextColumn::make('platform_role_label')
                    ->label('Role')
                    ->badge()
                    ->color(fn (User $record): string => match ($record->role) {
                        User::ROLE_PLATFORM_OWNER => 'danger',
                        User::ROLE_PLATFORM_ADMIN => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('account_status')
                    ->label('Status')
                    ->getStateUsing(fn (User $record): string => self::accountStatusLabel($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Archived' => 'gray',
                        'Deletion queued', 'Deletion processing' => 'warning',
                        'Deletion failed' => 'danger',
                        'Suspended' => 'danger',
                        'Password change required' => 'warning',
                        'Verification pending' => 'warning',
                        default => 'success',
                    })
                    ->description(fn (User $record): ?string => filled($record->account_deletion_status)
                        ? ($record->hasFailedAccountDeletion()
                            ? Str::limit($record->account_deletion_failure ?: 'Review the audit log, then retry the same deletion request.', 120)
                            : ucfirst((string) $record->account_deletion_project_disposition).' of owned projects')
                        : ($record->is_suspended && $record->suspension_category
                            ? (self::suspensionCategoryOptions()[$record->suspension_category] ?? $record->suspension_category)
                        : ($record->archived_at
                            ? 'Archived '.$record->archived_at->format('d M Y')
                            : ($record->email_verified_at === null ? 'Waiting for email confirmation' : null)))
                    ),
                TextColumn::make('email_verified_at')
                    ->label('Email verified')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Pending')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('archived_at')
                    ->label('Archived')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_suspended')
                    ->label('Suspended')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('plan')
                    ->label('Access')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PlanCatalog::displayPlanLabel($state))
                    ->color(fn (?string $state): string => PlanCatalog::canonicalPlanKey($state) === 'unlimited' ? 'success' : 'info'),
                TextColumn::make('billing_name')
                    ->label('Billing')
                    ->state(fn (User $record): string => $record->isUnlimitedAccount()
                        ? 'Not required'
                        : ($record->hasBillingDetails() ? $record->billing_name : 'Missing'))
                    ->description(fn (User $record): ?string => $record->billing_country ?: null)
                    ->badge()
                    ->color(fn (User $record): string => $record->isUnlimitedAccount() || $record->hasBillingDetails() ? 'success' : 'danger')
                    ->toggleable(),
                TextColumn::make('owned_projects_count')
                    ->label('Owned')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('projects_count')
                    ->label('Shared')
                    ->numeric()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subscription_status')
                    ->label('Access status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'expired', 'suspended' => 'danger',
                        default => 'success',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        User::ROLE_USER => 'User',
                        ...User::platformRoleOptions(),
                    ]),
                TernaryFilter::make('is_suspended')
                    ->label('Suspended'),
                Filter::make('must_change_password')
                    ->label('Password change required')
                    ->query(fn (Builder $query): Builder => $query->where('must_change_password', true)),
                Filter::make('platform_staff')
                    ->label('Platform staff')
                    ->query(fn (Builder $query): Builder => $query->whereIn('role', [User::ROLE_PLATFORM_OWNER, User::ROLE_PLATFORM_ADMIN, User::ROLE_ADMIN, User::ROLE_SUPERVISOR])),
                Filter::make('verification_pending')
                    ->label('Verification pending')
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at')),
                TernaryFilter::make('archived')
                    ->label('Archived')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('archived_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('archived_at'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View'),
                EditAction::make()
                    ->visible(fn (User $record): bool => blank($record->archived_at) && ! $record->trashed() && static::canManageAccount($record)),
                ActionGroup::make([
                    PlatformAccountNotificationAction::make(),
                    Action::make('impersonate')
                        ->label('Impersonate')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading(fn (User $record): string => 'Impersonate '.$record->email.'?')
                        ->modalDescription('Use impersonation only for a specific support or security case. You will be signed in as this user, the session will be audited, and the reason below must explain the exact operational need.')
                        ->form([
                            Textarea::make('reason')
                                ->label('Reason')
                                ->helperText('Required for audit and support traceability. Example: Support ticket #123, billing verification, debugging a reported issue.')
                                ->required()
                                ->minLength(8)
                                ->maxLength(1000)
                                ->rows(4),
                        ])
                        ->visible(fn (User $record): bool => (auth()->user()?->isPlatformAdmin() ?? false)
                            && ! $record->isPlatformAdmin()
                            && ! $record->is_suspended
                            && blank($record->archived_at)
                            && ! $record->trashed()
                            && $record->id !== auth()->id())
                        ->action(function (User $record, array $data) {
                            session()->put('impersonation_reason_'.$record->id, trim((string) $data['reason']));

                            return redirect()->route('platform.impersonation.start', $record);
                        }),
                    Action::make('resetPassword')
                        ->label('Reset password')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->visible(fn (User $record): bool => blank($record->archived_at) && ! $record->trashed() && static::canManageAccount($record))
                        ->form([
                            TextInput::make('password')
                                ->password()
                                ->revealable()
                                ->required()
                                ->minLength(8),
                            Toggle::make('must_change_password')
                                ->label('Require change after next login')
                                ->default(true),
                        ])
                        ->action(function (User $record, array $data): void {
                            if (! static::canManageAccount($record)) {
                                Notification::make()
                                    ->title('Action not allowed')
                                    ->body('Only platform owners can modify platform admin or owner accounts.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $record->update([
                                'password' => Hash::make($data['password']),
                                'must_change_password' => (bool) ($data['must_change_password'] ?? true),
                            ]);

                            PlatformAudit::log('account.password_reset', 'Password reset for '.$record->email, $record);
                            Notification::make()->title('Password reset')->success()->send();
                        }),
                    Action::make('suspend')
                        ->label('Suspend')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->visible(fn (User $record): bool => blank($record->archived_at) && ! $record->trashed() && ! $record->is_suspended && static::canManageAccount($record))
                        ->modalHeading(fn (User $record): string => 'Suspend '.$record->email.'?')
                        ->modalDescription('This blocks access to all client modules. The user will only see the suspended-account support page until a platform admin reactivates the account.')
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
                                ->helperText('This prevents accidental account blocking from the admin table.'),
                        ])
                        ->action(function (User $record, array $data): void {
                            if (! static::canManageAccount($record)) {
                                Notification::make()
                                    ->title('Action not allowed')
                                    ->body('Only platform owners can modify platform admin or owner accounts.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $record->update([
                                'is_suspended' => true,
                                'suspension_category' => $data['suspension_category'],
                                'suspension_reason' => $data['suspension_reason'],
                                'suspended_at' => now(),
                                'suspended_by' => auth()->id(),
                            ]);
                            PlatformAudit::log('account.suspended', 'Suspended '.$record->email, $record, [
                                'category' => $data['suspension_category'],
                            ]);
                            Notification::make()->title('Account suspended')->success()->send();
                        }),
                    Action::make('reactivate')
                        ->label('Reactivate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (User $record): bool => blank($record->archived_at) && ! $record->trashed() && $record->is_suspended && static::canManageAccount($record))
                        ->action(function (User $record): void {
                            if (! static::canManageAccount($record)) {
                                Notification::make()
                                    ->title('Action not allowed')
                                    ->body('Only platform owners can modify platform admin or owner accounts.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $record->update([
                                'is_suspended' => false,
                                'suspension_category' => null,
                                'suspension_reason' => null,
                                'suspended_at' => null,
                                'suspended_by' => null,
                            ]);
                            PlatformAudit::log('account.reactivated', 'Reactivated '.$record->email, $record);
                            Notification::make()->title('Account reactivated')->success()->send();
                        }),
                    Action::make('archive')
                        ->label('Archive account')
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->visible(fn (User $record): bool => blank($record->archived_at) && ! $record->trashed() && static::canPermanentlyDeleteAccount($record))
                        ->modalHeading(fn (User $record): string => 'Archive '.$record->email.'?')
                        ->modalDescription('Archived accounts cannot sign in and disappear from normal account lists, but they can be restored later by a platform owner.')
                        ->form([
                            Textarea::make('reason')
                                ->label('Archive reason')
                                ->required()
                                ->rows(4)
                                ->maxLength(2000),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update([
                                'archived_at' => now(),
                                'archived_by' => auth()->id(),
                                'archived_reason' => $data['reason'],
                                'is_suspended' => false,
                                'suspension_category' => null,
                                'suspension_reason' => null,
                                'suspended_at' => null,
                                'suspended_by' => null,
                            ]);

                            PlatformAudit::log('account.archived', 'Archived account '.$record->email, $record, [
                                'reason' => $data['reason'],
                            ]);

                            Notification::make()->title('Account archived')->success()->send();
                        })
                        ->modalSubmitActionLabel('Archive account'),
                    Action::make('restore')
                        ->label('Restore account')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->visible(fn (User $record): bool => filled($record->archived_at)
                            && blank($record->account_deletion_status)
                            && (auth()->user()?->canManagePlatformAdmins() ?? false))
                        ->requiresConfirmation()
                        ->modalHeading(fn (User $record): string => 'Restore '.$record->email.'?')
                        ->modalDescription('The account will become active in the admin list again. Review suspension and project access before handing it back to the user.')
                        ->action(function (User $record): void {
                            $record->update([
                                'archived_at' => null,
                                'archived_by' => null,
                                'archived_reason' => null,
                            ]);

                            PlatformAudit::log('account.restored', 'Restored archived account '.$record->email, $record);
                            Notification::make()->title('Account restored')->success()->send();
                        }),
                    Action::make('deletePermanently')
                        ->label('Delete permanently')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn (User $record): bool => static::canPermanentlyDeleteAccount($record))
                        ->modalHeading(fn (User $record): string => 'Permanently delete '.$record->email.'?')
                        ->modalDescription(fn (User $record): string => sprintf(
                            'This account owns %d active or archived project(s). Before deletion you must explicitly transfer every owned project to another active customer account or permanently purge the projects and all their stored files. The operation runs as a resumable background job and remains audited.',
                            $record->ownedProjects()->withTrashed()->count(),
                        ))
                        ->modalSubmitActionLabel('Queue permanent deletion')
                        ->form([
                            Select::make('project_disposition')
                                ->label('Owned projects')
                                ->options([
                                    AccountDeletionService::PROJECTS_TRANSFER => 'Transfer all projects to another account',
                                    AccountDeletionService::PROJECTS_PURGE => 'Permanently delete all owned projects and files',
                                ])
                                ->required()
                                ->live()
                                ->native(false)
                                ->helperText('This choice also covers archived projects and cannot be changed after the deletion job starts.'),
                            Select::make('transfer_account_id')
                                ->label('Transfer projects to')
                                ->options(fn (User $record): array => User::query()
                                    ->whereKeyNot($record->id)
                                    ->where('role', User::ROLE_USER)
                                    ->whereNull('archived_at')
                                    ->where('is_suspended', false)
                                    ->whereNull('account_deletion_status')
                                    ->orderBy('email')
                                    ->pluck('email', 'id')
                                    ->all())
                                ->searchable()
                                ->required(fn (callable $get): bool => $get('project_disposition') === AccountDeletionService::PROJECTS_TRANSFER)
                                ->visible(fn (callable $get): bool => $get('project_disposition') === AccountDeletionService::PROJECTS_TRANSFER)
                                ->native(false)
                                ->helperText('The receiving account becomes the owner of every active and archived project.'),
                            TextInput::make('purge_confirmation')
                                ->label('Type PURGE OWNED PROJECTS')
                                ->required(fn (callable $get): bool => $get('project_disposition') === AccountDeletionService::PROJECTS_PURGE)
                                ->visible(fn (callable $get): bool => $get('project_disposition') === AccountDeletionService::PROJECTS_PURGE)
                                ->helperText(fn (User $record): string => 'This permanently removes '.$record->ownedProjects()->withTrashed()->count().' project(s), including participants, financial evidence, grant proof and final archives.'),
                            TextInput::make('confirmation_email')
                                ->label('Type the account email to confirm')
                                ->required()
                                ->helperText(fn (User $record): string => 'Type exactly: '.$record->email.'. Use this only after export/backup and support checks are complete.'),
                        ])
                        ->action(function (User $record, array $data): void {
                            if (! static::canPermanentlyDeleteAccount($record)) {
                                Notification::make()
                                    ->title('Action not allowed')
                                    ->body('Only platform owners can permanently delete accounts. You cannot delete yourself or the last platform owner.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            if (($data['confirmation_email'] ?? null) !== $record->email) {
                                Notification::make()
                                    ->title('Email confirmation does not match')
                                    ->body('Type the account email exactly before permanently deleting it.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $projectDisposition = (string) ($data['project_disposition'] ?? '');
                            $transferAccountId = filled($data['transfer_account_id'] ?? null)
                                ? (int) $data['transfer_account_id']
                                : null;

                            if (! in_array($projectDisposition, [
                                AccountDeletionService::PROJECTS_TRANSFER,
                                AccountDeletionService::PROJECTS_PURGE,
                            ], true)) {
                                Notification::make()
                                    ->title('Choose what happens to owned projects')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            if ($projectDisposition === AccountDeletionService::PROJECTS_TRANSFER && ! $transferAccountId) {
                                Notification::make()
                                    ->title('Choose a transfer account')
                                    ->body('Owned projects cannot be left without an account owner.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            if ($projectDisposition === AccountDeletionService::PROJECTS_TRANSFER
                                && ! User::query()
                                    ->whereKey($transferAccountId)
                                    ->whereKeyNot($record->id)
                                    ->where('role', User::ROLE_USER)
                                    ->whereNull('archived_at')
                                    ->where('is_suspended', false)
                                    ->whereNull('account_deletion_status')
                                    ->exists()) {
                                Notification::make()
                                    ->title('Transfer account is no longer available')
                                    ->body('Choose another active customer account before queuing deletion.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            if ($projectDisposition === AccountDeletionService::PROJECTS_PURGE
                                && ($data['purge_confirmation'] ?? null) !== 'PURGE OWNED PROJECTS') {
                                Notification::make()
                                    ->title('Project purge confirmation does not match')
                                    ->body('Type PURGE OWNED PROJECTS exactly to approve permanent project and file removal.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $deletedEmail = $record->email;
                            $deletedId = (int) $record->id;
                            $actorId = (int) auth()->id();

                            try {
                                DB::transaction(function () use (
                                    $deletedEmail,
                                    $deletedId,
                                    $actorId,
                                    $projectDisposition,
                                    $transferAccountId,
                                ): void {
                                    $accountIds = collect([$deletedId, $actorId, $transferAccountId])
                                        ->filter()
                                        ->map(fn ($id): int => (int) $id)
                                        ->unique()
                                        ->sort()
                                        ->values();
                                    $accounts = User::withTrashed()
                                        ->whereIn('id', $accountIds)
                                        ->orderBy('id')
                                        ->lockForUpdate()
                                        ->get()
                                        ->keyBy('id');
                                    $lockedRecord = $accounts->get($deletedId);
                                    $actor = $accounts->get($actorId);

                                    if (! $lockedRecord
                                        || ! $actor?->canManagePlatformAdmins()
                                        || filled($actor->archived_at)
                                        || $actor->is_suspended
                                        || $actor->is($lockedRecord)) {
                                        throw new RuntimeException('The account deletion is no longer authorised.');
                                    }

                                    if (filled($lockedRecord->account_deletion_status)) {
                                        throw new RuntimeException('A permanent deletion request already exists for this account.');
                                    }

                                    if ($projectDisposition === AccountDeletionService::PROJECTS_TRANSFER) {
                                        $transferAccount = $accounts->get($transferAccountId);

                                        if (! $transferAccount
                                            || $transferAccount->trashed()
                                            || $transferAccount->role !== User::ROLE_USER
                                            || filled($transferAccount->archived_at)
                                            || $transferAccount->is_suspended
                                            || filled($transferAccount->account_deletion_status)) {
                                            throw new RuntimeException('The transfer account is no longer an active customer account.');
                                        }
                                    }

                                    $ownedProjectCount = $lockedRecord->ownedProjects()->withTrashed()->count();
                                    $sharedProjectCount = $lockedRecord->projects()->count();

                                    $lockedRecord->forceFill([
                                        'archived_at' => $lockedRecord->archived_at ?: now(),
                                        'archived_by' => $lockedRecord->archived_by ?: $actorId,
                                        'archived_reason' => $lockedRecord->archived_reason ?: 'Permanent account deletion requested.',
                                        'is_suspended' => true,
                                        'suspension_category' => 'client_request',
                                        'suspension_reason' => 'Permanent account deletion is being processed.',
                                        'suspended_at' => now(),
                                        'suspended_by' => $actorId,
                                        'account_deletion_status' => User::ACCOUNT_DELETION_QUEUED,
                                        'account_deletion_requested_at' => now(),
                                        'account_deletion_requested_by' => $actorId,
                                        'account_deletion_project_disposition' => $projectDisposition,
                                        'account_deletion_transfer_account_id' => $transferAccountId,
                                        'account_deletion_started_at' => null,
                                        'account_deletion_failure' => null,
                                    ])->save();

                                    PlatformAudit::log('account.deletion_requested', 'Queued permanent deletion for '.$deletedEmail, $lockedRecord, [
                                        'deleted_user_id' => $deletedId,
                                        'project_disposition' => $projectDisposition,
                                        'transfer_account_id' => $transferAccountId,
                                        'owned_projects' => $ownedProjectCount,
                                        'shared_project_access' => $sharedProjectCount,
                                    ]);

                                    DeletePlatformAccount::dispatch(
                                        $deletedId,
                                        $projectDisposition,
                                        $transferAccountId,
                                        $actorId,
                                        $deletedEmail,
                                    )->afterCommit();
                                }, 3);
                            } catch (RuntimeException $exception) {
                                Notification::make()
                                    ->title('Permanent deletion was not queued')
                                    ->body($exception->getMessage())
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Permanent deletion queued')
                                ->body($projectDisposition === AccountDeletionService::PROJECTS_TRANSFER
                                    ? 'The account is blocked immediately. Its projects will be transferred before the account is removed.'
                                    : 'The account is blocked immediately. Its projects and files will be removed before the account is deleted.')
                                ->success()
                                ->send();
                        }),
                    Action::make('retryPermanentDeletion')
                        ->label('Retry permanent deletion')
                        ->icon('heroicon-o-arrow-path')
                        ->color('danger')
                        ->visible(fn (User $record): bool => $record->hasFailedAccountDeletion()
                            && (auth()->user()?->canManagePlatformAdmins() ?? false))
                        ->requiresConfirmation()
                        ->modalHeading(fn (User $record): string => 'Retry deletion of '.$record->email.'?')
                        ->modalDescription(fn (User $record): string => 'The original '.($record->account_deletion_project_disposition ?: 'deletion').' decision is preserved. Last failure: '.($record->account_deletion_failure ?: 'Unknown error.'))
                        ->modalSubmitActionLabel('Retry the original request')
                        ->action(function (User $record): void {
                            $actorId = (int) auth()->id();
                            $disposition = (string) $record->account_deletion_project_disposition;
                            $transferAccountId = $record->account_deletion_transfer_account_id
                                ? (int) $record->account_deletion_transfer_account_id
                                : null;

                            try {
                                DB::transaction(function () use ($record, $actorId, $disposition, $transferAccountId): void {
                                    $accountIds = collect([$record->id, $actorId, $transferAccountId])
                                        ->filter()
                                        ->map(fn ($id): int => (int) $id)
                                        ->unique()
                                        ->sort()
                                        ->values();
                                    $accounts = User::withTrashed()
                                        ->whereIn('id', $accountIds)
                                        ->orderBy('id')
                                        ->lockForUpdate()
                                        ->get()
                                        ->keyBy('id');
                                    $lockedRecord = $accounts->get($record->id);
                                    $actor = $accounts->get($actorId);

                                    if (! $lockedRecord?->hasFailedAccountDeletion()
                                        || ! $actor?->canManagePlatformAdmins()
                                        || filled($actor->archived_at)
                                        || $actor->is_suspended) {
                                        throw new RuntimeException('The failed deletion request is no longer available.');
                                    }

                                    if (! in_array($disposition, [AccountDeletionService::PROJECTS_TRANSFER, AccountDeletionService::PROJECTS_PURGE], true)) {
                                        throw new RuntimeException('The original project decision is invalid and cannot be retried.');
                                    }

                                    if ($disposition === AccountDeletionService::PROJECTS_TRANSFER) {
                                        $transferAccount = $accounts->get($transferAccountId);

                                        if (! $transferAccount
                                            || $transferAccount->trashed()
                                            || $transferAccount->role !== User::ROLE_USER
                                            || filled($transferAccount->archived_at)
                                            || $transferAccount->is_suspended
                                            || filled($transferAccount->account_deletion_status)) {
                                            throw new RuntimeException('The original transfer account is no longer active. Reactivate it before retrying.');
                                        }
                                    }

                                    $lockedRecord->forceFill([
                                        'account_deletion_status' => User::ACCOUNT_DELETION_QUEUED,
                                        'account_deletion_requested_by' => $actorId,
                                        'account_deletion_failure' => null,
                                    ])->save();

                                    PlatformAudit::log('account.deletion_retried', 'Retried permanent deletion for '.$lockedRecord->email, $lockedRecord, [
                                        'project_disposition' => $disposition,
                                        'transfer_account_id' => $transferAccountId,
                                    ]);

                                    DeletePlatformAccount::dispatch(
                                        (int) $lockedRecord->id,
                                        $disposition,
                                        $transferAccountId,
                                        $actorId,
                                        (string) $lockedRecord->email,
                                    )->afterCommit();
                                }, 3);
                            } catch (RuntimeException $exception) {
                                Notification::make()
                                    ->title('Deletion retry was not queued')
                                    ->body($exception->getMessage())
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()->title('Permanent deletion queued again')->success()->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformUsers::route('/'),
            'create' => CreatePlatformUser::route('/create'),
            'view' => ViewPlatformUser::route('/{record}'),
            'edit' => EditPlatformUser::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SupportNotesRelationManager::class,
        ];
    }

    public static function suspensionCategoryOptions(): array
    {
        return [
            'billing_issue' => 'Billing issue',
            'security_review' => 'Security review',
            'manual_review' => 'Manual review',
            'client_request' => 'Client request',
            'policy_issue' => 'Policy issue',
        ];
    }

    public static function accountStatusLabel(User $record): string
    {
        if ($record->account_deletion_status === User::ACCOUNT_DELETION_QUEUED) {
            return 'Deletion queued';
        }

        if ($record->account_deletion_status === User::ACCOUNT_DELETION_PROCESSING) {
            return 'Deletion processing';
        }

        if ($record->account_deletion_status === User::ACCOUNT_DELETION_FAILED) {
            return 'Deletion failed';
        }

        if ($record->archived_at) {
            return 'Archived';
        }

        if ($record->is_suspended) {
            return 'Suspended';
        }

        if ($record->email_verified_at === null) {
            return 'Verification pending';
        }

        if ($record->must_change_password) {
            return 'Password change required';
        }

        return 'Active';
    }
}
