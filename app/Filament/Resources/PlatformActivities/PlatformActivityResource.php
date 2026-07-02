<?php

namespace App\Filament\Resources\PlatformActivities;

use App\Filament\Resources\PlatformActivities\Pages\ListPlatformActivities;
use App\Filament\Resources\PlatformActivities\Pages\ViewPlatformActivity;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Resources\PlatformWorkspaces\PlatformWorkspaceResource;
use App\Models\PlatformAuditLog;
use App\Models\User;
use App\Models\Workspace;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformActivityResource extends Resource
{
    protected static ?string $model = PlatformAuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|\UnitEnum|null $navigationGroup = 'Audit & operations';

    protected static ?string $navigationLabel = 'Activity center';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'activity';

    protected static ?string $pluralModelLabel = 'activity';

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

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isPlatformAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Audit event')
                    ->description('What happened, when it happened and how severe the action is.')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('action')
                            ->label('Action')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => self::actionLabel($state))
                            ->color(fn (string $state): string => self::actionColor($state)),
                        TextEntry::make('created_at')
                            ->label('Timestamp')
                            ->dateTime('d M Y, H:i:s')
                            ->copyable(),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->prose()
                            ->placeholder('No description recorded.'),
                    ]),
                Section::make('Actor & context')
                    ->description('Who triggered the event and from where.')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('actor.email')
                            ->label('Actor')
                            ->placeholder('System')
                            ->copyable()
                            ->url(fn (PlatformAuditLog $record): ?string => $record->actor
                                ? PlatformUserResource::getUrl('edit', ['record' => $record->actor], panel: 'platform')
                                : null),
                        TextEntry::make('actor.name')
                            ->label('Actor name')
                            ->placeholder('—'),
                        TextEntry::make('ip_address')
                            ->label('IP address')
                            ->placeholder('—')
                            ->copyable(),
                    ]),
                Section::make('Subject')
                    ->description('The account, workspace or object affected by the action.')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('subject_label')
                            ->label('Affected record')
                            ->state(fn (PlatformAuditLog $record): string => self::subjectLabel($record))
                            ->url(fn (PlatformAuditLog $record): ?string => self::subjectUrl($record))
                            ->placeholder('—'),
                        TextEntry::make('subject_type')
                            ->label('Type')
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                        TextEntry::make('subject_id')
                            ->label('Record ID')
                            ->placeholder('—')
                            ->copyable(),
                    ]),
                Section::make('Audit metadata')
                    ->description('Structured operational details captured with the event.')
                    ->columnSpan(2)
                    ->schema([
                        KeyValueEntry::make('metadata')
                            ->label('Metadata')
                            ->state(fn (PlatformAuditLog $record): array => self::formattedMetadataForDisplay($record))
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->placeholder('No metadata recorded.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('actor'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable()
                    ->description(fn (PlatformAuditLog $record): string => $record->created_at?->format('d M Y, H:i:s') ?? ''),
                TextColumn::make('actor.email')
                    ->label('Actor')
                    ->placeholder('System')
                    ->searchable()
                    ->description(fn (PlatformAuditLog $record): ?string => $record->actor?->name),
                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::actionLabel($state))
                    ->color(fn (string $state): string => self::actionColor($state))
                    ->searchable(),
                TextColumn::make('description')
                    ->wrap()
                    ->searchable()
                    ->limit(140)
                    ->description(fn (PlatformAuditLog $record): ?string => self::metadataSummary($record)),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->description(fn (PlatformAuditLog $record): ?string => $record->subject_id ? '#'.$record->subject_id : null)
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('actor_id')
                    ->label('Actor')
                    ->options(fn (): array => User::query()
                        ->whereIn('id', PlatformAuditLog::query()->whereNotNull('actor_id')->select('actor_id'))
                        ->orderBy('name')
                        ->pluck('email', 'id')
                        ->all())
                    ->searchable(),
                SelectFilter::make('action_group')
                    ->label('Action type')
                    ->options([
                        'accounts' => 'Accounts',
                        'subscriptions' => 'Subscriptions',
                        'impersonation' => 'Impersonation',
                        'announcements' => 'Announcements',
                        'support' => 'Support notes',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'accounts' => $query->where('action', 'like', 'account.%'),
                            'subscriptions' => $query->where(function (Builder $query): void {
                                $query
                                    ->where('action', 'like', 'workspace.%')
                                    ->orWhere('action', 'like', 'subscription_%');
                            }),
                            'impersonation' => $query->where('action', 'like', 'impersonation.%'),
                            'announcements' => $query->where('action', 'like', 'announcement.%'),
                            'support' => $query->where('action', 'like', 'support_note.%'),
                            default => $query,
                        };
                    }),
                SelectFilter::make('action')
                    ->options(fn (): array => PlatformAuditLog::query()
                        ->select('action')
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all())
                    ->searchable(),
                SelectFilter::make('subject_type')
                    ->label('Subject type')
                    ->options(fn (): array => PlatformAuditLog::query()
                        ->whereNotNull('subject_type')
                        ->select('subject_type')
                        ->distinct()
                        ->orderBy('subject_type')
                        ->pluck('subject_type', 'subject_type')
                        ->mapWithKeys(fn (string $type, string $key): array => [$key => class_basename($type)])
                        ->all()),
                Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', today())),
                Filter::make('last_7_days')
                    ->label('Last 7 days')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => self::exportCsv()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Details'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function actionLabel(string $action): string
    {
        return str($action)->replace(['.', '_'], ' ')->title();
    }

    public static function actionColor(string $action): string
    {
        return match (true) {
            str_contains($action, 'deleted'), str_contains($action, 'suspended'), str_contains($action, 'expired') => 'danger',
            str_contains($action, 'impersonation'), str_contains($action, 'billing'), str_contains($action, 'manual_access') => 'warning',
            str_contains($action, 'created'), str_contains($action, 'activated'), str_contains($action, 'reactivated') => 'success',
            str_contains($action, 'updated'), str_contains($action, 'plan'), str_contains($action, 'trial') => 'info',
            default => 'gray',
        };
    }

    public static function metadataSummary(PlatformAuditLog $record): ?string
    {
        if (! is_array($record->metadata) || $record->metadata === []) {
            return null;
        }

        $parts = collect($record->metadata)
            ->take(3)
            ->map(function (mixed $value, string $key): string {
                if (is_array($value)) {
                    $value = self::compactArrayValue($value);
                }

                return str($key)->replace('_', ' ')->title().': '.str((string) $value)->limit(60);
            })
            ->values();

        return $parts->join(' · ');
    }

    public static function formattedMetadataForDisplay(PlatformAuditLog $record): array
    {
        if (! is_array($record->metadata) || $record->metadata === []) {
            return [];
        }

        return collect($record->metadata)
            ->mapWithKeys(function (mixed $value, string $key): array {
                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                } elseif (is_array($value)) {
                    $value = self::compactArrayValue($value, pretty: true);
                } elseif ($value === null) {
                    $value = '—';
                }

                return [(string) str($key)->replace('_', ' ')->title() => (string) $value];
            })
            ->all();
    }

    protected static function compactArrayValue(array $value, bool $pretty = false): string
    {
        if (array_key_exists('from', $value) && array_key_exists('to', $value)) {
            return self::formatScalarForDisplay($value['from'] ?? null).' → '.self::formatScalarForDisplay($value['to'] ?? null);
        }

        if (isset($value['changes']) && is_array($value['changes'])) {
            return collect($value['changes'])
                ->map(fn (mixed $change, string $field): string => str($field)->replace('_', ' ')->title().': '.(is_array($change) ? self::compactArrayValue($change) : self::formatScalarForDisplay($change)))
                ->join("\n");
        }

        return json_encode($value, ($pretty ? JSON_PRETTY_PRINT : 0) | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    protected static function formatScalarForDisplay(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        if (is_array($value)) {
            return self::compactArrayValue($value);
        }

        return (string) $value;
    }

    public static function exportCsv()
    {
        $filename = 'platform-activity-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Time', 'Actor', 'Action', 'Description', 'Subject type', 'Subject ID', 'IP address', 'Metadata']);

            PlatformAuditLog::query()
                ->with('actor')
                ->latest()
                ->limit(5000)
                ->lazy()
                ->each(function (PlatformAuditLog $log) use ($handle): void {
                    fputcsv($handle, [
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->actor?->email ?? 'System',
                        $log->action,
                        $log->description,
                        $log->subject_type ? class_basename($log->subject_type) : '',
                        $log->subject_id,
                        $log->ip_address,
                        json_encode($log->metadata ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ]);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function subjectLabel(PlatformAuditLog $record): string
    {
        $subject = $record->subject;

        if ($subject instanceof User) {
            return trim(($subject->name ? $subject->name.' · ' : '').$subject->email);
        }

        if ($subject instanceof Workspace) {
            return $subject->name;
        }

        if ($record->subject_type && $record->subject_id) {
            return class_basename($record->subject_type).' #'.$record->subject_id.' (deleted or unavailable)';
        }

        return 'No subject';
    }

    public static function subjectUrl(PlatformAuditLog $record): ?string
    {
        $subject = $record->subject;

        if ($subject instanceof User && PlatformUserResource::canManageAccount($subject)) {
            return PlatformUserResource::getUrl('edit', ['record' => $subject], panel: 'platform');
        }

        if ($subject instanceof Workspace) {
            return PlatformWorkspaceResource::getUrl('edit', ['record' => $subject], panel: 'platform');
        }

        return null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformActivities::route('/'),
            'view' => ViewPlatformActivity::route('/{record}'),
        ];
    }
}
