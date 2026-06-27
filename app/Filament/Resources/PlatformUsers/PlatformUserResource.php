<?php

namespace App\Filament\Resources\PlatformUsers;

use App\Filament\Resources\PlatformUsers\Pages\CreatePlatformUser;
use App\Filament\Resources\PlatformUsers\Pages\EditPlatformUser;
use App\Filament\Resources\PlatformUsers\Pages\ListPlatformUsers;
use App\Models\User;
use App\Support\PlatformAudit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->description('Global account data. Workspace permissions remain managed inside each workspace.')
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
                        ->inline(false),
                    Toggle::make('must_change_password')
                        ->label('Require password change')
                        ->inline(false),
                    Textarea::make('support_notes')
                        ->label('Internal support notes')
                        ->rows(5)
                        ->maxLength(3000)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('workspaces'))
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
                    ->getStateUsing(fn (User $record): string => $record->is_suspended ? 'Suspended' : ($record->must_change_password ? 'Password change required' : 'Active'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Suspended' => 'danger',
                        'Password change required' => 'warning',
                        default => 'success',
                    }),
                IconColumn::make('is_suspended')
                    ->label('Suspended')
                    ->boolean(),
                TextColumn::make('workspaces_count')
                    ->label('Workspaces')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('projects_total')
                    ->label('Projects')
                    ->getStateUsing(fn (User $record): int => $record->workspaces()
                        ->withCount('projects')
                        ->get()
                        ->sum('projects_count'))
                    ->alignEnd(),
                TextColumn::make('plan_summary')
                    ->label('Plans')
                    ->getStateUsing(fn (User $record): string => $record->workspaces()
                        ->pluck('plan')
                        ->unique()
                        ->sort()
                        ->map(fn (string $plan): string => str($plan)->replace('_', ' ')->title())
                        ->join(', ') ?: '—')
                    ->toggleable(),
                TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        User::ROLE_USER => 'User',
                        ...User::platformRoleOptions(),
                    ]),
                TernaryFilter::make('is_suspended')
                    ->label('Suspended'),
            ])
            ->recordActions([
                Action::make('resetPassword')
                    ->label('Reset password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
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
                        $record->update([
                            'password' => Hash::make($data['password']),
                            'must_change_password' => (bool) ($data['must_change_password'] ?? true),
                        ]);

                        PlatformAudit::log('account.password_reset', 'Password reset for '.$record->email, $record);
                        Notification::make()->title('Password reset')->success()->send();
                    }),
                ActionGroup::make([
                    Action::make('suspend')
                        ->label('Suspend')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (User $record): bool => ! $record->is_suspended)
                        ->action(function (User $record): void {
                            $record->update(['is_suspended' => true]);
                            PlatformAudit::log('account.suspended', 'Suspended '.$record->email, $record);
                            Notification::make()->title('Account suspended')->success()->send();
                        }),
                    Action::make('reactivate')
                        ->label('Reactivate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (User $record): bool => $record->is_suspended)
                        ->action(function (User $record): void {
                            $record->update(['is_suspended' => false]);
                            PlatformAudit::log('account.reactivated', 'Reactivated '.$record->email, $record);
                            Notification::make()->title('Account reactivated')->success()->send();
                        }),
                    EditAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformUsers::route('/'),
            'create' => CreatePlatformUser::route('/create'),
            'edit' => EditPlatformUser::route('/{record}/edit'),
        ];
    }
}
