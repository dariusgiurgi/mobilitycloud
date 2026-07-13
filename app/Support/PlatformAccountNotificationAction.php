<?php

namespace App\Support;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class PlatformAccountNotificationAction
{
    public static function make(string $name = 'sendAccountNotification'): Action
    {
        return Action::make($name)
            ->label('Send notification')
            ->icon('heroicon-o-bell-alert')
            ->color('info')
            ->visible(fn (User $record): bool => self::canSendTo($record))
            ->modalHeading(fn (User $record): string => 'Send notification · '.$record->email)
            ->modalDescription('This creates an in-app notification for this account. Use it for account-specific support, billing, onboarding or operational messages.')
            ->form([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(120)
                    ->placeholder('Important account update'),
                Textarea::make('body')
                    ->label('Message')
                    ->required()
                    ->minLength(3)
                    ->maxLength(2000)
                    ->rows(5)
                    ->columnSpanFull(),
                Select::make('tone')
                    ->label('Tone')
                    ->options([
                        'info' => 'Info',
                        'success' => 'Success',
                        'warning' => 'Warning',
                        'danger' => 'Critical',
                    ])
                    ->default('info')
                    ->required()
                    ->native(false),
                TextInput::make('url')
                    ->label('Optional link')
                    ->url()
                    ->maxLength(2048)
                    ->placeholder('https://mobilitycloud.eu/app'),
                TextInput::make('action_label')
                    ->label('Link button label')
                    ->maxLength(60)
                    ->placeholder('Open')
                    ->helperText('Used only when an optional link is provided.'),
            ])
            ->action(function (User $record, array $data): void {
                if (! self::canSendTo($record)) {
                    Notification::make()
                        ->title('Action not allowed')
                        ->body('You cannot send account notifications to this account.')
                        ->danger()
                        ->send();

                    return;
                }

                self::sendTo($record, $data);
            });
    }

    public static function canSendTo(User $record): bool
    {
        $actor = auth()->user();

        if (! $actor?->isPlatformAdmin()) {
            return false;
        }

        if ($record->trashed() || filled($record->archived_at) || $record->is($actor)) {
            return false;
        }

        return $actor->canManagePlatformAdmins() || ! $record->isPlatformAdmin();
    }

    public static function sendTo(User $record, array $data): void
    {
        $tone = in_array($data['tone'] ?? 'info', ['info', 'success', 'warning', 'danger'], true)
            ? $data['tone']
            : 'info';

        $notification = Notification::make()
            ->title(trim((string) $data['title']))
            ->body(trim((string) $data['body']))
            ->viewData([
                'kind' => 'platform_account_message',
                'tone' => $tone,
                'sent_by' => auth()->id(),
                'sent_by_name' => auth()->user()?->name,
                'sent_at' => now()->toIso8601String(),
            ]);

        match ($tone) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            default => $notification->info(),
        };

        $url = trim((string) ($data['url'] ?? ''));
        if ($url !== '') {
            $notification->actions([
                Action::make('openPlatformNotificationLink')
                    ->label(filled($data['action_label'] ?? null) ? trim((string) $data['action_label']) : 'Open')
                    ->button()
                    ->markAsRead()
                    ->url($url),
            ]);
        }

        $notification->sendToDatabase($record, isEventDispatched: true);

        PlatformAudit::log('account.notification_sent', 'Sent account notification to '.$record->email, $record, [
            'title' => trim((string) $data['title']),
            'tone' => $tone,
            'has_link' => $url !== '',
        ]);

        Notification::make()
            ->title('Notification sent')
            ->body('The message was added to '.$record->email.' notifications.')
            ->success()
            ->send();
    }
}
