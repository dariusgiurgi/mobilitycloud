<?php

namespace Tests\Feature;

use App\Filament\Pages\DocumentTemplates;
use App\Filament\Pages\GlobalSearch;
use App\Filament\Pages\IndividualSupportCalculator;
use App\Filament\Pages\MyTasks;
use App\Filament\Pages\ProjectCalendar;
use App\Filament\Pages\PublicLibrary;
use App\Filament\Resources\ContentBlocks\ContentBlockResource;
use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use App\Filament\Resources\PlatformUsers\Pages\ListPlatformUsers;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\PublicBlockReports\PublicBlockReportResource;
use App\Filament\Resources\PublicContentBlocks\PublicContentBlockResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformAdminInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_owner_uses_internal_interface_instead_of_product_modules(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);

        $this->actingAs($owner);

        $this->assertProductModulesAreUnavailable();
        $this->assertTrue(PublicBlockReportResource::shouldRegisterNavigation());
        $this->assertTrue(PublicBlockReportResource::canAccess());
        $this->assertTrue(PlatformUserResource::canAccess());
        $this->assertTrue(PlatformAnnouncementResource::canAccess());
    }

    public function test_platform_admin_uses_same_internal_interface_as_platform_owner(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin);

        $this->assertProductModulesAreUnavailable();
        $this->assertTrue(PublicBlockReportResource::shouldRegisterNavigation());
        $this->assertTrue(PublicBlockReportResource::canAccess());
    }

    public function test_regular_user_keeps_the_normal_product_interface(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user);

        $this->assertTrue(ProjectResource::canAccess());
        $this->assertTrue(ContentBlockResource::canAccess());
        $this->assertTrue(GlobalSearch::canAccess());
        $this->assertFalse(PublicBlockReportResource::shouldRegisterNavigation());
    }

    public function test_platform_accounts_mark_unverified_users_as_pending_verification(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        User::factory()->unverified()->create([
            'name' => 'Pending Client',
            'email' => 'pending@example.test',
        ]);

        $this->actingAs($owner);
        Filament::setCurrentPanel('platform');

        $this->assertSame(
            'Verification pending',
            PlatformUserResource::accountStatusLabel(User::where('email', 'pending@example.test')->firstOrFail()),
        );

        Livewire::test(ListPlatformUsers::class)
            ->assertSee('Pending Client')
            ->assertSee('Verification pending')
            ->assertSee('Pending verification');
    }

    private function assertProductModulesAreUnavailable(): void
    {
        foreach ([
            ProjectResource::class,
            ContentBlockResource::class,
            PublicContentBlockResource::class,
            GlobalSearch::class,
            MyTasks::class,
            ProjectCalendar::class,
            IndividualSupportCalculator::class,
            PublicLibrary::class,
            DocumentTemplates::class,
        ] as $module) {
            $this->assertFalse($module::canAccess(), $module.' should be hidden from platform staff.');
        }
    }
}
