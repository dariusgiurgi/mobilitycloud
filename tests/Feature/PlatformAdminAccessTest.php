<?php

namespace Tests\Feature;

use App\Filament\Resources\PublicBlockReports\PublicBlockReportResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderation_reports_are_hidden_and_blocked_for_regular_users(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]));

        $this->assertFalse(PublicBlockReportResource::shouldRegisterNavigation());
        $this->assertFalse(PublicBlockReportResource::canAccess());
    }

    public function test_moderation_reports_are_not_available_to_supervisor_accounts(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERVISOR]));

        $this->assertFalse(PublicBlockReportResource::shouldRegisterNavigation());
        $this->assertFalse(PublicBlockReportResource::canAccess());
    }

    public function test_moderation_reports_are_available_to_platform_admin_accounts(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->assertTrue(PublicBlockReportResource::shouldRegisterNavigation());
        $this->assertTrue(PublicBlockReportResource::canAccess());
    }
}
