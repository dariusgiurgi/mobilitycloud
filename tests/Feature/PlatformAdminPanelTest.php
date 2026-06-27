<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\PlatformAnnouncements\Pages\CreatePlatformAnnouncement;
use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use App\Filament\Resources\PlatformAuditLogs\PlatformAuditLogResource;
use App\Filament\Resources\PlatformUsers\Pages\CreatePlatformUser;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Resources\PlatformWorkspaces\Pages\EditPlatformWorkspace;
use App\Filament\Resources\PlatformWorkspaces\PlatformWorkspaceResource;
use App\Models\PlatformAnnouncement;
use App\Models\PlatformAuditLog;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_staff_can_access_internal_management_resources(): void
    {
        [$workspace, $owner] = $this->platformUser(User::ROLE_PLATFORM_OWNER);
        $this->actingAs($owner);
        Filament::setTenant($workspace);

        $this->assertTrue(PlatformUserResource::canAccess());
        $this->assertTrue(PlatformWorkspaceResource::canAccess());
        $this->assertTrue(PlatformAnnouncementResource::canAccess());
        $this->assertTrue(PlatformAuditLogResource::canAccess());

        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $this->actingAs($admin);

        $this->assertTrue(PlatformUserResource::canAccess());
        $this->assertTrue(PlatformWorkspaceResource::canAccess());
        $this->assertTrue(PlatformAnnouncementResource::canAccess());
        $this->assertFalse(PlatformAuditLogResource::canAccess());
    }

    public function test_regular_users_cannot_access_internal_management_resources(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $this->assertFalse(PlatformUserResource::canAccess());
        $this->assertFalse(PlatformWorkspaceResource::canAccess());
        $this->assertFalse(PlatformAnnouncementResource::canAccess());
        $this->assertFalse(PlatformAuditLogResource::canAccess());
    }

    public function test_platform_owner_can_create_internal_account_from_panel(): void
    {
        [$workspace, $owner] = $this->platformUser(User::ROLE_PLATFORM_OWNER);
        $this->actingAs($owner);
        Filament::setTenant($workspace);

        Livewire::test(CreatePlatformUser::class)
            ->fillForm([
                'name' => 'Support Admin',
                'email' => 'support@example.test',
                'password' => 'temporary-password',
                'role' => User::ROLE_PLATFORM_ADMIN,
                'is_suspended' => false,
                'must_change_password' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::where('email', 'support@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_PLATFORM_ADMIN, $created->role);
        $this->assertTrue(Hash::check('temporary-password', $created->password));
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'account.created',
            'subject_id' => $created->id,
        ]);
    }

    public function test_platform_admin_can_update_workspace_subscription_controls(): void
    {
        [$workspace, $admin] = $this->platformUser(User::ROLE_PLATFORM_ADMIN);
        $clientWorkspace = Workspace::create(['name' => 'Client Org', 'plan' => 'free']);

        $this->actingAs($admin);
        Filament::setTenant($workspace);

        Livewire::test(EditPlatformWorkspace::class, ['record' => $clientWorkspace->id])
            ->fillForm([
                'name' => 'Client Org',
                'billing_name' => 'Client Legal Org',
                'billing_vat' => 'RO123',
                'billing_address' => 'Bucharest',
                'plan' => 'writer_pro',
                'subscription_status' => 'trial',
                'trial_ends_at' => '2026-07-15 10:00:00',
                'subscription_ends_at' => '2026-08-15 10:00:00',
                'is_suspended' => false,
                'is_internal' => false,
                'internal_notes' => 'Manual support override.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $clientWorkspace->refresh();
        $this->assertSame('writer_pro', $clientWorkspace->plan);
        $this->assertSame('trial', $clientWorkspace->subscription_status);
        $this->assertSame('Manual support override.', $clientWorkspace->internal_notes);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'workspace.updated',
            'subject_id' => $clientWorkspace->id,
        ]);
    }

    public function test_platform_admin_can_create_header_announcement_visible_in_panel(): void
    {
        [$workspace, $admin] = $this->platformUser(User::ROLE_PLATFORM_ADMIN);
        $this->actingAs($admin);
        Filament::setTenant($workspace);

        Livewire::test(CreatePlatformAnnouncement::class)
            ->fillForm([
                'title' => 'Maintenance tonight',
                'message' => 'Document generation will be unavailable for 15 minutes.',
                'severity' => 'maintenance',
                'audience' => 'all',
                'starts_at' => null,
                'ends_at' => null,
                'is_active' => true,
                'is_dismissible' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('platform_announcements', [
            'title' => 'Maintenance tonight',
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'announcement.created',
        ]);

        $this->get(Dashboard::getUrl(tenant: $workspace))
            ->assertOk()
            ->assertSee('Maintenance tonight')
            ->assertSee('Document generation will be unavailable');
    }

    public function test_announcement_audience_can_target_specific_plans(): void
    {
        $announcement = PlatformAnnouncement::create([
            'title' => 'Writer Pro notice',
            'message' => 'Advanced feature update.',
            'severity' => 'info',
            'audience' => 'plans',
            'plans' => ['writer_pro'],
            'is_active' => true,
        ]);
        $user = User::factory()->create();

        $this->assertTrue($announcement->isVisibleFor($user, Workspace::create(['name' => 'Pro', 'plan' => 'writer_pro'])));
        $this->assertFalse($announcement->isVisibleFor($user, Workspace::create(['name' => 'Free', 'plan' => 'free'])));
    }

    private function platformUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Internal Admin']);
        $user = User::factory()->create(['role' => $role]);
        $workspace->users()->attach($user, ['role' => 'owner', 'joined_at' => now()]);

        return [$workspace, $user];
    }
}
