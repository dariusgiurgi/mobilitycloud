<?php

namespace Tests\Feature;

use App\Filament\Pages\DocumentTemplates;
use App\Filament\Pages\GlobalSearch;
use App\Filament\Pages\IndividualSupportCalculator;
use App\Filament\Pages\ManageCurrencies;
use App\Filament\Pages\NotificationPreferences;
use App\Filament\Pages\AccountSettings;
use App\Filament\Pages\PublicLibrary;
use App\Filament\Pages\WorkspaceCalendar;
use App\Filament\Pages\WorkspaceData;
use App\Filament\Pages\WorkspaceReports;
use App\Filament\Resources\ContentBlocks\ContentBlockResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\PublicBlockReports\PublicBlockReportResource;
use App\Filament\Resources\PublicContentBlocks\PublicContentBlockResource;
use Filament\Facades\Filament;
use Tests\TestCase;

class NavigationStructureTest extends TestCase
{
    public function test_sidebar_is_organized_around_the_user_workflow(): void
    {
        $this->assertNull(ProjectResource::getNavigationGroup());
        $this->assertSame('Projects', ProjectResource::getNavigationLabel());
        $this->assertSame(-1, ProjectResource::getNavigationSort());
        $this->assertNull(GlobalSearch::getNavigationGroup());
        $this->assertSame(1, GlobalSearch::getNavigationSort());
        $this->assertNull(WorkspaceCalendar::getNavigationGroup());
        $this->assertSame(2, WorkspaceCalendar::getNavigationSort());
        $this->assertNull(WorkspaceReports::getNavigationGroup());
        $this->assertSame('Portfolio reports', WorkspaceReports::getNavigationLabel());
        $this->assertSame(3, WorkspaceReports::getNavigationSort());

        $this->assertSame('Planning tools', ContentBlockResource::getNavigationGroup());
        $this->assertSame('Writing Library', ContentBlockResource::getNavigationLabel());
        $this->assertSame(10, ContentBlockResource::getNavigationSort());
        $this->assertFalse(IndividualSupportCalculator::shouldRegisterNavigation());

        $this->assertSame('Community', PublicLibrary::getNavigationGroup());
        $this->assertSame(10, PublicLibrary::getNavigationSort());
        $this->assertSame('Platform management', PublicBlockReportResource::getNavigationGroup());
        $this->assertSame('Moderation reports', PublicBlockReportResource::getNavigationLabel());
        $this->assertSame(40, PublicBlockReportResource::getNavigationSort());

        $this->assertFalse(ManageCurrencies::shouldRegisterNavigation());
        $this->assertSame('Workspace settings', WorkspaceData::getNavigationGroup());
        $this->assertSame('Backup & exports', WorkspaceData::getNavigationLabel());
        $this->assertSame(30, WorkspaceData::getNavigationSort());
        $this->assertSame('Workspace settings', DocumentTemplates::getNavigationGroup());
        $this->assertSame(35, DocumentTemplates::getNavigationSort());
        $this->assertFalse(NotificationPreferences::shouldRegisterNavigation());

        $this->assertFalse(AccountSettings::shouldRegisterNavigation());
        $this->assertSame('My account', AccountSettings::getNavigationLabel());
    }

    public function test_technical_public_library_resource_stays_out_of_the_sidebar(): void
    {
        $this->assertFalse(PublicContentBlockResource::shouldRegisterNavigation());
    }

    public function test_panel_registers_navigation_groups_in_priority_order(): void
    {
        $groups = Filament::getPanel('admin')->getNavigationGroups();

        $this->assertSame(
            ['Platform management', 'Operations', 'Planning tools', 'Community', 'Workspace settings'],
            array_map(fn ($group) => $group->getLabel(), $groups),
        );

        foreach ($groups as $group) {
            $this->assertFalse($group->isCollapsible());
        }
    }
}
