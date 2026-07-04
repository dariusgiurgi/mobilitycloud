<?php

namespace Tests\Feature;

use App\Filament\Pages\AccountSettings;
use App\Models\User;
use App\Models\Workspace;
use App\Support\PlanCatalog;
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
        [$workspace, $user] = $this->workspaceAndUser('member');
        $this->actingAs($user);
        Filament::setTenant($workspace);

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
        [$workspace, $user] = $this->workspaceAndUser('member');
        $this->actingAs($user);
        Filament::setTenant($workspace);

        $items = Filament::getPanel('admin')->getUserMenuItems();

        $this->assertFalse(AccountSettings::shouldRegisterNavigation());
        $this->assertArrayHasKey('accountSettings', $items);
        $this->assertSame('My account', $items['accountSettings']->getLabel());
        $this->assertSame(AccountSettings::getUrl(), $items['accountSettings']->getUrl());
    }

    public function test_user_can_update_password_from_account_center(): void
    {
        [$workspace, $user] = $this->workspaceAndUser('member');
        $user->update(['password' => Hash::make('old-password')]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

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
        [$workspace, $user] = $this->workspaceAndUser('member');
        $this->actingAs($user);
        Filament::setTenant($workspace);

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

    public function test_workspace_owner_can_change_subscription_plan_from_account_center(): void
    {
        [$workspace, $user] = $this->workspaceAndUser('owner', ['plan' => 'free']);
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(AccountSettings::class)
            ->assertSee('Subscription')
            ->set('subscriptionWorkspaceId', $workspace->id)
            ->set('subscriptionPlan', 'writer_pro')
            ->call('saveSubscriptionPlan');

        $this->assertSame('writer_pro', $workspace->fresh()->plan);
        $this->assertSame(PlanCatalog::defaultModules('writer_pro'), $workspace->fresh()->feature_flags);
        $this->assertSame(PlanCatalog::defaultLimits('writer_pro'), $workspace->fresh()->plan_limits);
    }

    public function test_workspace_viewer_cannot_change_subscription_plan(): void
    {
        [$workspace, $user] = $this->workspaceAndUser('viewer', ['plan' => 'free']);
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(AccountSettings::class)
            ->set('subscriptionWorkspaceId', $workspace->id)
            ->set('subscriptionPlan', 'writer')
            ->call('saveSubscriptionPlan')
            ->assertForbidden();

        $this->assertSame('free', $workspace->fresh()->plan);
    }

    public function test_user_can_see_multiple_workspaces_and_switch_current_workspace(): void
    {
        [$workspace, $user] = $this->workspaceAndUser('member');
        $second = Workspace::create(['name' => 'Second Organisation']);
        $second->users()->attach($user, ['role' => 'admin', 'joined_at' => now()]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(AccountSettings::class)
            ->assertSee('Account Workspace')
            ->assertSee('Second Organisation')
            ->call('switchWorkspace', $second->id);

        $this->assertSame($second->id, $user->fresh()->current_workspace_id);
    }

    private function workspaceAndUser(string $role, array $workspaceAttributes = []): array
    {
        $workspace = Workspace::create(array_merge(['name' => 'Account Workspace'], $workspaceAttributes));
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role, 'joined_at' => now()]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return [$workspace, $user];
    }
}
