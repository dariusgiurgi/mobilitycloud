<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'My account';

    protected static string|\UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Account Center';

    protected string $view = 'filament.pages.account-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public bool $taskAssigned = true;

    public bool $taskDueSoon = true;

    public bool $taskOverdue = true;

    public string $defaultLanding = 'dashboard';

    public string $interfaceDensity = 'comfortable';

    public string $subscriptionPlan = 'standard';

    public string $billingName = '';

    public string $billingVat = '';

    public string $billingCountry = '';

    public string $billingAddress = '';

    public function mount(): void
    {
        $user = auth()->user();
        $preferences = $user->notification_preferences ?? [];

        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->taskAssigned = $user->wantsNotification('task_assigned');
        $this->taskDueSoon = $user->wantsNotification('task_due_soon');
        $this->taskOverdue = $user->wantsNotification('task_overdue');
        $this->defaultLanding = (string) data_get(
            $preferences,
            $this->isPlatformPanel() ? 'platform_admin.default_landing' : 'platform.default_landing',
            'dashboard',
        );
        $this->interfaceDensity = (string) data_get($preferences, 'platform.interface_density', 'comfortable');

        if ($this->isPlatformPanel()) {
            return;
        }

        $this->subscriptionPlan = (string) ($user->plan ?? 'standard');
        $this->billingName = (string) ($user->billing_name ?? '');
        $this->billingVat = (string) ($user->billing_vat ?? '');
        $this->billingCountry = (string) ($user->billing_country ?? '');
        $this->billingAddress = (string) ($user->billing_address ?? '');
    }

    public function getSubheading(): ?string
    {
        if ($this->isPlatformPanel()) {
            return 'Manage your platform administrator profile, security and interface preferences.';
        }

        return 'Manage your personal profile, security, billing identity and platform preferences.';
    }

    public function saveProfile(): void
    {
        $user = auth()->user();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->id)],
        ]);

        $user->update([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
        ]);

        Notification::make()->title('Account details saved')->success()->send();
    }

    public function updatePassword(): void
    {
        $data = $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => ['required', 'string', 'min:8', 'same:newPasswordConfirmation'],
            'newPasswordConfirmation' => ['required', 'string'],
        ], [
            'currentPassword.current_password' => 'The current password is not correct.',
            'newPassword.same' => 'The password confirmation does not match.',
        ]);

        auth()->user()->update([
            'password' => Hash::make($data['newPassword']),
        ]);

        $this->reset('currentPassword', 'newPassword', 'newPasswordConfirmation');

        Notification::make()->title('Password updated')->success()->send();
    }

    public function savePreferences(): void
    {
        $data = $this->validate([
            'defaultLanding' => ['required', Rule::in(array_keys($this->landingOptions()))],
            'interfaceDensity' => ['required', Rule::in(array_keys($this->densityOptions()))],
            'taskAssigned' => ['boolean'],
            'taskDueSoon' => ['boolean'],
            'taskOverdue' => ['boolean'],
        ]);

        $preferences = auth()->user()->notification_preferences ?? [];

        if ($this->isPlatformPanel()) {
            data_set($preferences, 'platform_admin.default_landing', $data['defaultLanding']);
        } else {
            data_set($preferences, 'task_assigned', (bool) $data['taskAssigned']);
            data_set($preferences, 'task_due_soon', (bool) $data['taskDueSoon']);
            data_set($preferences, 'task_overdue', (bool) $data['taskOverdue']);
            data_set($preferences, 'platform.default_landing', $data['defaultLanding']);
        }

        data_set($preferences, 'platform.interface_density', $data['interfaceDensity']);

        auth()->user()->update(['notification_preferences' => $preferences]);

        Notification::make()->title('Account preferences saved')->success()->send();
    }

    public function saveBillingDetails(): void
    {
        abort_if($this->isPlatformPanel(), 404);

        $data = $this->validate([
            'billingName' => ['required', 'string', 'max:255'],
            'billingVat' => ['nullable', 'string', 'max:255'],
            'billingCountry' => ['required', 'string', 'max:255'],
            'billingAddress' => ['required', 'string', 'max:2000'],
        ]);

        auth()->user()->update([
            'billing_name' => trim($data['billingName']),
            'billing_vat' => trim($data['billingVat'] ?? ''),
            'billing_country' => trim($data['billingCountry']),
            'billing_address' => trim($data['billingAddress']),
        ]);

        Notification::make()
            ->title('Billing details saved')
            ->body('These fiscal details will be used when approved project invoices are issued.')
            ->success()
            ->send();
    }

    public function getCurrentAccountProperty(): ?User
    {
        return auth()->user();
    }

    public function getManageableAccountsProperty()
    {
        return collect([auth()->user()])->filter();
    }

    public function planOptions(): array
    {
        return [
            'standard' => 'Standard',
            'unlimited' => 'Unlimited',
        ];
    }

    public function landingOptions(): array
    {
        if ($this->isPlatformPanel()) {
            return [
                'dashboard' => 'Platform overview',
                'users' => 'Users',
                'subscriptions' => 'Account access',
                'audit' => 'Audit log',
            ];
        }

        return [
            'dashboard' => 'Project dashboard',
            'projects' => 'Projects',
            'tasks' => 'My Tasks',
        ];
    }

    public function densityOptions(): array
    {
        return [
            'comfortable' => 'Comfortable',
            'compact' => 'Compact',
        ];
    }

    public function isPlatformPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'platform';
    }
}
