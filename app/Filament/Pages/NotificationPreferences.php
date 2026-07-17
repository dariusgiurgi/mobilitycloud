<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class NotificationPreferences extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notifications';

    protected static string|\UnitEnum|null $navigationGroup = 'Account settings';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Notification preferences';

    protected string $view = 'filament.pages.notification-preferences';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public bool $taskAssigned = true;

    public bool $taskDueSoon = true;

    public bool $taskOverdue = true;

    public function mount(): void
    {
        $user = auth()->user();
        $this->taskAssigned = $user->wantsNotification('task_assigned');
        $this->taskDueSoon = $user->wantsNotification('task_due_soon');
        $this->taskOverdue = $user->wantsNotification('task_overdue');
    }

    public function getSubheading(): ?string
    {
        return 'Choose which task events appear in your in-app notification centre.';
    }

    public function save(): void
    {
        auth()->user()->update(['notification_preferences' => [
            'task_assigned' => $this->taskAssigned,
            'task_due_soon' => $this->taskDueSoon,
            'task_overdue' => $this->taskOverdue,
        ]]);

        Notification::make()->title('Notification preferences saved')->success()->send();
    }
}
