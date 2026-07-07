<?php

namespace Tests\Feature;

use App\Filament\Pages\DocumentTemplates;
use App\Filament\Pages\GlobalSearch;
use App\Filament\Pages\IndividualSupportCalculator;
use App\Filament\Pages\ManageCurrencies;
use App\Filament\Pages\MyTasks;
use App\Filament\Pages\PublicLibrary;
use App\Filament\Pages\WorkspaceCalendar;
use App\Filament\Pages\WorkspaceData;
use App\Filament\Pages\WorkspaceReports;
use App\Filament\Resources\ContentBlocks\ContentBlockResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\PublicBlockReports\PublicBlockReportResource;
use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Resources\PublicContentBlocks\PublicContentBlockResource;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAdminInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_owner_uses_internal_interface_instead_of_workspace_modules(): void
    {
        [$workspace, $owner] = $this->platformUserInWorkspace(User::ROLE_PLATFORM_OWNER);

        $this->actingAs($owner);
        Filament::setTenant($workspace);

        $this->assertWorkspaceModulesAreUnavailable();
        $this->assertTrue(PublicBlockReportResource::shouldRegisterNavigation());
        $this->assertTrue(PublicBlockReportResource::canAccess());
        $this->assertTrue(PlatformUserResource::canAccess());
        $this->assertTrue(PlatformAnnouncementResource::canAccess());
    }

    public function test_platform_admin_uses_same_internal_interface_as_platform_owner(): void
    {
        [$workspace, $admin] = $this->platformUserInWorkspace(User::ROLE_PLATFORM_ADMIN);

        $this->actingAs($admin);
        Filament::setTenant($workspace);

        $this->assertWorkspaceModulesAreUnavailable();
        $this->assertTrue(PublicBlockReportResource::shouldRegisterNavigation());
        $this->assertTrue(PublicBlockReportResource::canAccess());
    }

    public function test_regular_workspace_admin_keeps_the_normal_workspace_interface(): void
    {
        $workspace = Workspace::create(['name' => 'Client Workspace']);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $workspace->users()->attach($user, ['role' => 'admin', 'joined_at' => now()]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        $this->assertTrue(ProjectResource::canAccess());
        $this->assertTrue(ContentBlockResource::canAccess());
        $this->assertTrue(GlobalSearch::canAccess());
        $this->assertFalse(PublicBlockReportResource::shouldRegisterNavigation());
    }

    private function assertWorkspaceModulesAreUnavailable(): void
    {
        foreach ([
            ProjectResource::class,
            ContentBlockResource::class,
            PublicContentBlockResource::class,
            GlobalSearch::class,
            MyTasks::class,
            WorkspaceCalendar::class,
            WorkspaceReports::class,
            IndividualSupportCalculator::class,
            PublicLibrary::class,
            ManageCurrencies::class,
            WorkspaceData::class,
            DocumentTemplates::class,
        ] as $module) {
            $this->assertFalse($module::canAccess(), $module.' should be hidden from platform staff.');
        }
    }

    private function platformUserInWorkspace(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Internal Workspace']);
        $user = User::factory()->create(['role' => $role]);
        $workspace->users()->attach($user, ['role' => 'owner', 'joined_at' => now()]);

        return [$workspace, $user];
    }
}
