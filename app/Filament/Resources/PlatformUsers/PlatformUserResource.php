<?php

namespace App\Filament\Resources\PlatformUsers;

use App\Filament\Resources\PlatformUsers\Pages\CreatePlatformUser;
use App\Filament\Resources\PlatformUsers\Pages\EditPlatformUser;
use App\Filament\Resources\PlatformUsers\Pages\ListPlatformUsers;
use App\Filament\Resources\PlatformUsers\Pages\ViewPlatformUser;
use App\Filament\Resources\PlatformUsers\RelationManagers\SupportNotesRelationManager;
use App\Models\User;
use App\Support\PlatformAudit;
use App\Support\PlanCatalog;
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

        if ($actor->canManagePlatformAdmins()) {
            return true;
        }

        return ! $record->isPlatformAdmin();
    }

    public static function canPermanentlyDeleteAccount(User $record): bool
    {
        $actor = auth()->user();

        if (! $actor?->canManagePlatformAdmins()) {
            return false;
        }

        if ($record->is($actor)) {
            return false;
        }

        if ($record->isPlatformOwner() && User::withTrashed()->whereIn('role', [User::ROLE_PLATFORM_OWNER, User::ROLE_ADMIN])->count() <= 1) {
            return false;
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
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->minLength(8)
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
                        ->required(fn (callable $get): bool => (bool) $get('is_suspended'))
                        ->visible(fn (callable $get): bool => (bool) $get('is_suspended')),
                    Textarea::make('suspension_reason')
                        ->label('Suspension reason')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull()
                        ->required(fn (callable $get): bool => (bool) $get('is_suspended'))
                        ->visible(fn (callable $get): bool => (bool) $get('is_suspended')),
                    Toggle::make('must_change_password')
                        ->label('Require password change')
                        ->inline(false),
                    Textarea::make('support_notes')
                        ->label('Internal support notes')
                        ->rows(5)
                        ->maxLength(3000)
                        ->columnSpanFull(),
                    Select::make('plan')
                        ->label('Subscription plan')
                        ->options(PlanCatalog::planOptions())
                        ->default('free')
                        ->required(),
                    Select::make('subscription_status')
                        ->label('Subscription status')
                        ->options([
                            'active' => 'Active',
                            'trial' => 'Trial',
                            'demo' => 'Demo',
                            'expired' => 'Expired',
                            'suspended' => 'Suspended',
                        ])
                        ->default('active')
                        ->required(),
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
                            ->state(fn (User $record): string => $record->archived_at ? 'Archived' : ($record->is_suspended ? 'Suspended' : ($record->must_change_password ? 'Password change required' : 'Active')))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Archived' => 'gray',
                                'Suspended' => 'danger',
                                'Password change required' => 'warning',
                                default => 'success',
                            }),
                        TextEntry::make('plan')
                            ->label('Plan')
                            ->formatStateUsing(fn (?string $state): string => PlanCatalog::planOptions()[$state ?: 'free'] ?? ucfirst((string) $state)),
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
                    ->getStateUsing(fn (User $record): string => $record->archived_at ? 'Archived' : ($record->is_suspended ? 'Suspended' : ($record->must_change_password ? 'Password change required' : 'Active')))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Archived' => 'gray',
                        'Suspended' => 'danger',
                        'Password change required' => 'warning',
                        default => 'success',
                    })
                    ->description(fn (User $record): ?string => $record->is_suspended && $record->suspension_category
                        ? (self::suspensionCategoryOptions()[$record->suspension_category] ?? $record->suspension_category)
                        : ($record->archived_at ? 'Archived '.$record->archived_at->format('d M Y') : null)
                    ),
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
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PlanCatalog::planOptions()[$state ?: 'free'] ?? ucfirst((string) $state))
                    ->color('info'),
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
                    ->label('Subscription')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'trial' => 'warning',
                        'demo' => 'info',
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
                        ->visible(fn (User $record): bool => filled($record->archived_at) && (auth()->user()?->canManagePlatformAdmins() ?? false))
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
                        ->modalDescription('This is irreversible. It removes the account, project memberships and account-owned public activity that is configured to cascade. Historical audit entries remain where required for platform accountability.')
                        ->modalSubmitActionLabel('Delete account permanently')
                        ->form([
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

                            $deletedEmail = $record->email;
                            $deletedId = $record->id;
                            $ownedProjectCount = $record->ownedProjects()->count();
                            $sharedProjectCount = $record->projects()->count();

                            DB::transaction(function () use ($record, $deletedEmail, $deletedId, $ownedProjectCount, $sharedProjectCount): void {
                                PlatformAudit::log('account.deleted_permanently', 'Permanently deleted account '.$deletedEmail, $record, [
                                    'deleted_user_id' => $deletedId,
                                    'owned_projects' => $ownedProjectCount,
                                    'shared_project_access' => $sharedProjectCount,
                                ]);

                                $record->forceDelete();
                            });

                            Notification::make()
                                ->title('Account permanently deleted')
                                ->body($deletedEmail.' was removed from the platform.')
                                ->success()
                                ->send();
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

}
