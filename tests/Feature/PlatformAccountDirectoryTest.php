<?php

namespace Tests\Feature;

use App\Filament\Resources\PlatformUsers\Pages\ListPlatformUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformAccountDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]));
        Filament::setCurrentPanel('platform');
    }

    public function test_accounts_can_be_found_by_email_and_organisation(): void
    {
        $target = User::factory()->create(['name' => 'Ana Popescu', 'email' => 'office@distinct.test', 'billing_name' => 'Youth Association']);
        $other = User::factory()->create(['name' => 'Other client']);
        foreach (['office@distinct.test', 'Youth Association', 'Ana Popescu'] as $search) {
            Livewire::test(ListPlatformUsers::class)->searchTable($search)
                ->assertCanSeeTableRecords([$target])->assertCanNotSeeTableRecords([$other]);
        }
    }

    public function test_active_category_excludes_accounts_requiring_attention(): void
    {
        $active = User::factory()->create();
        $suspended = User::factory()->create(['is_suspended' => true]);
        $unverified = User::factory()->unverified()->create();
        $password = User::factory()->create(['must_change_password' => true]);
        $archived = User::factory()->create(['archived_at' => now()]);
        $component = Livewire::test(ListPlatformUsers::class)->set('activeTab', 'active');
        $component->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$suspended, $unverified, $password, $archived]);
        $tabs = $component->instance()->getTabs();
        $this->assertSame('2', $tabs['active']->getBadge());
        $this->assertSame('3', $tabs['attention']->getBadge());
        $this->assertSame('1', $tabs['pending_verification']->getBadge());
        $this->assertSame('1', $tabs['archived']->getBadge());
        $component->set('activeTab', 'attention')->assertCanSeeTableRecords([$suspended, $unverified, $password])->assertCanNotSeeTableRecords([$active, $archived]);
    }

    public function test_tariff_and_access_filters_can_be_combined_with_search(): void
    {
        $custom = User::factory()->create(['name' => 'Negotiated organisation']);
        $custom->forceFill(['project_fee_mode' => 'fixed', 'project_fee_value' => 300])->save();
        $global = User::factory()->create(['name' => 'Global organisation']);
        Livewire::test(ListPlatformUsers::class)->searchTable('organisation')
            ->filterTable('project_fee_mode', 'fixed')
            ->assertCanSeeTableRecords([$custom])->assertCanNotSeeTableRecords([$global]);
    }

    public function test_category_totals_refresh_after_an_account_action(): void
    {
        $suspended = User::factory()->create(['is_suspended' => true]);
        $component = Livewire::test(ListPlatformUsers::class)->callTableAction('reactivate', $suspended);
        $this->assertSame('2', $component->instance()->getCachedTabs()['active']->getBadge());
        $this->assertSame('0', $component->instance()->getCachedTabs()['attention']->getBadge());
    }

    public function test_failed_deletions_remain_discoverable_for_support(): void
    {
        $failed = User::factory()->create(['archived_at' => now(), 'account_deletion_status' => User::ACCOUNT_DELETION_FAILED]);
        Livewire::test(ListPlatformUsers::class)->set('activeTab', 'attention')->assertCanSeeTableRecords([$failed]);
    }
}
