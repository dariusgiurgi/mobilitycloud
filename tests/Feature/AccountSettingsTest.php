<?php

namespace Tests\Feature;

use App\Filament\Pages\AccountSettings;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_from_account_center(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(AccountSettings::class)
            ->assertSee('Account Center')
            ->assertSee('Personal details')
            ->set('name', 'Darius Mobility')
            ->set('email', 'darius@example.test')
            ->call('saveProfile');

        $user->refresh();
        $this->assertSame('Darius Mobility', $user->name);
        $this->assertSame('darius@example.test', $user->email);
    }

    public function test_account_center_is_available_from_the_user_menu_not_the_sidebar(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $items = Filament::getPanel('admin')->getUserMenuItems();

        $this->assertFalse(AccountSettings::shouldRegisterNavigation());
        $this->assertArrayHasKey('accountSettings', $items);
        $this->assertSame('My account', $items['accountSettings']->getLabel());
        $this->assertSame(
            AccountSettings::getUrl(),
            $items['accountSettings']->getUrl(),
        );
    }

    public function test_user_can_update_password_from_account_center(): void
    {
        $user = User::factory()->create();
        $user->update(['password' => Hash::make('old-password')]);

        $this->actingAs($user);

        Livewire::test(AccountSettings::class)
            ->set('currentPassword', 'old-password')
            ->set('newPassword', 'new-password-2026')
            ->set('newPasswordConfirmation', 'new-password-2026')
            ->call('updatePassword')
            ->assertSet('currentPassword', '')
            ->assertSet('newPassword', '')
            ->assertSet('newPasswordConfirmation', '');

        $this->assertTrue(Hash::check('new-password-2026', $user->fresh()->password));
    }

    public function test_user_can_save_platform_and_notification_preferences(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(AccountSettings::class)
            ->set('defaultLanding', 'tasks')
            ->set('interfaceDensity', 'compact')
            ->set('taskAssigned', false)
            ->set('taskDueSoon', true)
            ->set('taskOverdue', false)
            ->call('savePreferences');

        $preferences = $user->fresh()->notification_preferences;
        $this->assertFalse($preferences['task_assigned']);
        $this->assertTrue($preferences['task_due_soon']);
        $this->assertFalse($preferences['task_overdue']);
        $this->assertSame('tasks', $preferences['platform']['default_landing']);
        $this->assertSame('compact', $preferences['platform']['interface_density']);
    }

    public function test_account_center_shows_current_access_without_self_service_plan_changes(): void
    {
        $user = User::factory()->create();
        $user->update(['plan' => 'standard']);

        $this->actingAs($user);

        Livewire::test(AccountSettings::class)
            ->assertSee('Account access')
            ->assertSee('Standard')
            ->assertDontSee('Project plan');

        $this->assertSame('standard', $user->fresh()->plan);
    }

    public function test_platform_admin_account_center_does_not_expose_public_plan_changes(): void
    {
        $user = User::factory()->create();
        $user->update(['plan' => 'standard', 'role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($user);
        Filament::setCurrentPanel('platform');

        Livewire::test(AccountSettings::class)
            ->assertDontSee('Project plan')
            ->assertDontSee('Save subscription');

        $this->assertSame('standard', $user->fresh()->plan);
    }

    public function test_account_center_has_no_organisation_switching_for_regular_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(AccountSettings::class)
            ->assertDontSee('Your organisations')
            ->assertDontSee('Second Organisation');
    }
}
