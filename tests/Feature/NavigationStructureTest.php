<?php

namespace Tests\Feature;

use App\Filament\Pages\IndividualSupportCalculator;
use App\Filament\Pages\ManageCurrencies;
use App\Filament\Pages\PublicLibrary;
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

        $this->assertSame('Planning tools', ContentBlockResource::getNavigationGroup());
        $this->assertSame(10, ContentBlockResource::getNavigationSort());
        $this->assertSame('Planning tools', IndividualSupportCalculator::getNavigationGroup());
        $this->assertSame(20, IndividualSupportCalculator::getNavigationSort());

        $this->assertSame('Community', PublicLibrary::getNavigationGroup());
        $this->assertSame(10, PublicLibrary::getNavigationSort());
        $this->assertSame('Community', PublicBlockReportResource::getNavigationGroup());
        $this->assertSame('Moderation reports', PublicBlockReportResource::getNavigationLabel());
        $this->assertSame(20, PublicBlockReportResource::getNavigationSort());

        $this->assertSame('Workspace settings', ManageCurrencies::getNavigationGroup());
        $this->assertSame(10, ManageCurrencies::getNavigationSort());
    }

    public function test_technical_public_library_resource_stays_out_of_the_sidebar(): void
    {
        $this->assertFalse(PublicContentBlockResource::shouldRegisterNavigation());
    }

    public function test_panel_registers_navigation_groups_in_priority_order(): void
    {
        $groups = Filament::getPanel('admin')->getNavigationGroups();

        $this->assertSame(
            ['Planning tools', 'Community', 'Workspace settings'],
            array_map(fn ($group) => $group->getLabel(), $groups),
        );

        foreach ($groups as $group) {
            $this->assertFalse($group->isCollapsible());
        }
    }
}
