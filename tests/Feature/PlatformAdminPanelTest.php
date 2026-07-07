<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\AccountSettings;
use App\Filament\Pages\ManageCurrencies;
use App\Filament\Pages\PlatformHealth;
use App\Filament\Pages\PlatformPermissions;
use App\Filament\Pages\PlatformPlans;
use App\Filament\Resources\PlatformActivities\PlatformActivityResource;
use App\Filament\Resources\PlatformAnnouncements\Pages\CreatePlatformAnnouncement;
use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use App\Filament\Resources\PlatformAuditLogs\PlatformAuditLogResource;
use App\Filament\Resources\PlatformSubscriptions\Pages\ListPlatformSubscriptions;
use App\Filament\Resources\PlatformSubscriptions\PlatformSubscriptionResource;
use App\Filament\Resources\PlatformUsers\Pages\ListPlatformUsers;
use App\Filament\Resources\PlatformUsers\Pages\CreatePlatformUser;
use App\Filament\Resources\PlatformUsers\Pages\EditPlatformUser;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Resources\PlatformUsers\RelationManagers\SupportNotesRelationManager;
use App\Filament\Resources\PlatformWorkspaces\Pages\EditPlatformWorkspace;
use App\Filament\Resources\PlatformWorkspaces\Pages\ListPlatformWorkspaces;
use App\Filament\Resources\PlatformWorkspaces\PlatformWorkspaceResource;
use App\Filament\Resources\PlatformWorkspaces\RelationManagers\SubscriptionEventsRelationManager;
use App\Filament\Resources\PlatformWorkspaces\RelationManagers\WorkspaceNotesRelationManager;
use App\Models\ContentBlock;
use App\Models\PlatformAnnouncement;
use App\Models\PlatformAuditLog;
use App\Models\PlatformSupportNote;
use App\Models\PlatformWorkspaceNote;
use App\Models\Project;
use App\Models\SavedCalculation;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Support\PlanCatalog;
use App\Support\AccountAccess;
use App\Support\WorkspaceAccess;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\Login;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_staff_can_access_internal_management_resources(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $this->actingAs($owner);

        $this->assertTrue(PlatformUserResource::canAccess());
        $this->assertTrue(PlatformPlans::canAccess());
        $this->assertTrue(PlatformPermissions::canAccess());
        $this->assertTrue(PlatformHealth::canAccess());
        $this->assertTrue(PlatformActivityResource::canAccess());
        $this->assertTrue(PlatformSubscriptionResource::canAccess());
        $this->assertTrue(PlatformWorkspaceResource::canAccess());
        $this->assertTrue(PlatformAnnouncementResource::canAccess());
        $this->assertTrue(PlatformAuditLogResource::canAccess());

        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $this->actingAs($admin);

        $this->assertTrue(PlatformUserResource::canAccess());
        $this->assertTrue(PlatformPlans::canAccess());
        $this->assertTrue(PlatformPermissions::canAccess());
        $this->assertTrue(PlatformHealth::canAccess());
        $this->assertTrue(PlatformActivityResource::canAccess());
        $this->assertTrue(PlatformSubscriptionResource::canAccess());
        $this->assertTrue(PlatformWorkspaceResource::canAccess());
        $this->assertTrue(PlatformAnnouncementResource::canAccess());
        $this->assertFalse(PlatformAuditLogResource::canAccess());
    }

    public function test_regular_users_cannot_access_internal_management_resources(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $this->assertFalse(PlatformUserResource::canAccess());
        $this->assertFalse(PlatformPlans::canAccess());
        $this->assertFalse(PlatformPermissions::canAccess());
        $this->assertFalse(PlatformHealth::canAccess());
        $this->assertFalse(PlatformActivityResource::canAccess());
        $this->assertFalse(PlatformSubscriptionResource::canAccess());
        $this->assertFalse(PlatformWorkspaceResource::canAccess());
        $this->assertFalse(PlatformAnnouncementResource::canAccess());
        $this->assertFalse(PlatformAuditLogResource::canAccess());
    }

    public function test_platform_owner_can_create_internal_account_from_panel(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $this->actingAs($owner);
        $this->usePlatformPanel();

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
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $clientWorkspace = Workspace::create(['name' => 'Client Org', 'plan' => 'free']);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(EditPlatformWorkspace::class, ['record' => $clientWorkspace->id])
            ->fillForm([
                'name' => 'Client Org',
                'billing_name' => 'Client Legal Org',
                'billing_vat' => 'RO123',
                'billing_address' => 'Bucharest',
                'plan' => 'writer_pro',
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays(10)->startOfMinute()->format('Y-m-d H:i:s'),
                'subscription_ends_at' => now()->addDays(40)->startOfMinute()->format('Y-m-d H:i:s'),
                'is_suspended' => false,
                'is_internal' => false,
                'internal_notes' => 'Manual support override.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $clientWorkspace->refresh();
        $this->assertSame('writer_pro', $clientWorkspace->plan);
        $this->assertSame(PlanCatalog::defaultModules('writer_pro'), $clientWorkspace->feature_flags);
        $this->assertSame(PlanCatalog::defaultLimits('writer_pro'), $clientWorkspace->plan_limits);
        $this->assertSame('active', $clientWorkspace->subscription_status);
        $this->assertSame('Manual support override.', $clientWorkspace->internal_notes);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'workspace.updated',
            'subject_id' => $clientWorkspace->id,
        ]);
        $auditLog = PlatformAuditLog::query()
            ->where('action', 'workspace.updated')
            ->where('subject_id', $clientWorkspace->id)
            ->latest()
            ->firstOrFail();
        $this->assertSame('free', data_get($auditLog->metadata, 'changes.plan.from'));
        $this->assertSame('writer_pro', data_get($auditLog->metadata, 'changes.plan.to'));
        $this->assertDatabaseHas('platform_subscription_events', [
            'workspace_id' => $clientWorkspace->id,
            'actor_id' => $admin->id,
            'event_type' => 'plan_changed',
        ]);
    }

    public function test_platform_account_and_workspace_have_read_only_detail_pages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => 'Detail User',
            'email' => 'detail.user@example.test',
        ]);
        $workspace = Workspace::create([
            'name' => 'Detail Workspace',
            'plan' => 'writer_pro',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(5),
        ]);
        $workspace->users()->attach($user, ['role' => 'owner']);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        $this->get(PlatformUserResource::getUrl('view', ['record' => $user], panel: 'platform'))
            ->assertOk()
            ->assertSee('Detail User')
            ->assertSee('detail.user@example.test')
            ->assertSee('Account overview')
            ->assertSee('Operational context')
            ->assertSee('Active account');

        $this->get(PlatformWorkspaceResource::getUrl('view', ['record' => $workspace], panel: 'platform'))
            ->assertOk()
            ->assertSee('Detail Workspace')
            ->assertSee('Workspace overview')
            ->assertSee('Subscription &amp; access', false)
            ->assertSee('Writer Pro')
            ->assertSee('Trial');
    }

    public function test_platform_admin_can_prepare_workspace_billing_metadata(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $workspace = Workspace::create([
            'name' => 'Billing Ready Workspace',
            'plan' => 'writer',
            'subscription_status' => 'active',
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(EditPlatformWorkspace::class, ['record' => $workspace->id])
            ->fillForm([
                'name' => 'Billing Ready Workspace',
                'plan' => 'writer',
                'subscription_status' => 'active',
                'is_suspended' => false,
                'is_internal' => false,
                'billing_interval' => 'yearly',
                'billing_amount' => 1200,
                'billing_currency' => 'eur',
                'billing_reference' => 'MC-2026-001',
                'billing_provider' => 'stripe',
                'billing_provider_customer_id' => 'cus_test_123',
                'billing_provider_subscription_id' => 'sub_test_456',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $workspace->refresh();
        $this->assertSame('yearly', $workspace->billing_interval);
        $this->assertSame('1200.00', $workspace->billing_amount);
        $this->assertSame('EUR', $workspace->billing_currency);
        $this->assertSame('MC-2026-001', $workspace->billing_reference);
        $this->assertSame('stripe', $workspace->billing_provider);
        $this->assertSame('cus_test_123', $workspace->billing_provider_customer_id);
        $this->assertSame('sub_test_456', $workspace->billing_provider_subscription_id);
        $this->assertDatabaseHas('platform_subscription_events', [
            'workspace_id' => $workspace->id,
            'actor_id' => $admin->id,
            'event_type' => 'billing_updated',
        ]);
    }

    public function test_extend_trial_table_action_restores_access_for_expired_suspended_workspace(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $workspace = Workspace::create([
            'name' => 'Blocked Trial Workspace',
            'subscription_status' => 'expired',
            'subscription_ends_at' => now()->subDays(10),
            'is_suspended' => true,
        ]);

        $this->assertFalse(WorkspaceAccess::isSubscriptionActive($workspace));
        $this->assertTrue(WorkspaceAccess::isReadOnly($workspace));

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->callTableAction('extendTrial', $workspace)
            ->assertHasNoTableActionErrors();

        $workspace->refresh();
        $this->assertSame('trial', $workspace->subscription_status);
        $this->assertFalse($workspace->is_suspended);
        $this->assertNull($workspace->subscription_ends_at);
        $this->assertTrue($workspace->trial_ends_at->isFuture());
        $this->assertTrue(WorkspaceAccess::isSubscriptionActive($workspace));
        $this->assertFalse(WorkspaceAccess::isReadOnly($workspace));
    }

    public function test_admin_can_set_custom_trial_period_from_workspace_table(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $workspace = Workspace::create([
            'name' => 'Custom Trial Workspace',
            'subscription_status' => 'expired',
            'subscription_ends_at' => now()->subDays(10),
            'is_suspended' => true,
        ]);
        $trialEnd = now()->addDays(45)->startOfMinute();

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->callTableAction('setTrialPeriod', $workspace, data: [
                'trial_ends_at' => $trialEnd->format('Y-m-d H:i:s'),
            ])
            ->assertHasNoTableActionErrors();

        $workspace->refresh();
        $this->assertSame('trial', $workspace->subscription_status);
        $this->assertFalse($workspace->is_suspended);
        $this->assertNull($workspace->subscription_ends_at);
        $this->assertTrue($trialEnd->equalTo($workspace->trial_ends_at));
        $this->assertTrue(WorkspaceAccess::isSubscriptionActive($workspace));
        $this->assertFalse(WorkspaceAccess::isReadOnly($workspace));
        $this->assertDatabaseHas('platform_subscription_events', [
            'workspace_id' => $workspace->id,
            'actor_id' => $admin->id,
            'event_type' => 'trial_updated',
        ]);
    }

    public function test_owner_can_mark_workspace_as_demo_subscription(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $workspace = Workspace::create([
            'name' => 'Demo Candidate Workspace',
            'plan' => 'free',
            'subscription_status' => 'expired',
            'subscription_ends_at' => now()->subDays(10),
            'is_suspended' => true,
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->assertTableActionHidden('markDemo', $workspace);

        $this->actingAs($owner);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->assertSee('Owner only · Mark as demo')
            ->callTableAction('markDemo', $workspace, data: [
                'internal_notes' => 'Demo for sales presentation.',
            ])
            ->assertHasNoTableActionErrors();

        $workspace->refresh();
        $this->assertSame('demo', $workspace->plan);
        $this->assertSame('demo', $workspace->subscription_status);
        $this->assertTrue($workspace->is_internal);
        $this->assertFalse($workspace->is_suspended);
        $this->assertSame('Demo for sales presentation.', $workspace->internal_notes);
        $this->assertNull($workspace->trial_ends_at);
        $this->assertNull($workspace->subscription_ends_at);
        $this->assertSame(PlanCatalog::defaultModules('demo'), $workspace->feature_flags);
        $this->assertSame(PlanCatalog::defaultLimits('demo'), $workspace->plan_limits);
        $this->assertTrue(WorkspaceAccess::isSubscriptionActive($workspace));
        $this->assertFalse(WorkspaceAccess::isReadOnly($workspace));
        $this->assertDatabaseHas('platform_subscription_events', [
            'workspace_id' => $workspace->id,
            'actor_id' => $owner->id,
            'event_type' => 'demo_enabled',
        ]);
    }

    public function test_owner_can_reset_demo_workspace_sandbox_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $member = User::factory()->create(['role' => User::ROLE_USER]);
        $workspace = Workspace::create([
            'name' => 'Resettable Demo Workspace',
            'plan' => 'demo',
            'subscription_status' => 'demo',
            'demo_reset_frequency' => 'manual',
            'document_settings' => ['footer' => 'Keep this branding'],
        ]);
        $workspace->users()->attach($member, ['role' => 'owner']);

        Project::create(['workspace_id' => $workspace->id, 'name' => 'Sandbox Project']);
        ContentBlock::create([
            'workspace_id' => $workspace->id,
            'title' => 'Reusable demo text',
            'category' => 'needs',
            'body' => 'This is demo library content.',
        ]);
        SavedCalculation::create([
            'workspace_id' => $workspace->id,
            'created_by' => $member->id,
            'name' => 'Demo calculation',
            'inputs' => ['days' => 5],
            'results' => ['total' => 500],
        ]);
        WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'invited_by' => $member->id,
            'email' => 'invited@example.test',
            'role' => 'member',
            'token' => str()->random(64),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->assertTableActionHidden('resetDemoData', $workspace);

        $this->actingAs($owner);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->callTableAction('resetDemoData', $workspace, data: [
                'confirmation' => 'RESET DEMO',
            ])
            ->assertHasNoTableActionErrors();

        $workspace->refresh();
        $this->assertDatabaseMissing('projects', ['workspace_id' => $workspace->id, 'name' => 'Sandbox Project']);
        $this->assertDatabaseMissing('content_blocks', ['workspace_id' => $workspace->id, 'title' => 'Reusable demo text']);
        $this->assertDatabaseMissing('saved_calculations', ['workspace_id' => $workspace->id, 'name' => 'Demo calculation']);
        $this->assertDatabaseMissing('workspace_invitations', ['workspace_id' => $workspace->id, 'email' => 'invited@example.test']);
        $this->assertSame('demo', $workspace->plan);
        $this->assertSame('demo', $workspace->subscription_status);
        $this->assertSame(['footer' => 'Keep this branding'], $workspace->document_settings);
        $this->assertTrue($workspace->users()->whereKey($member->id)->exists());
        $this->assertNotNull($workspace->demo_last_reset_at);
        $this->assertDatabaseHas('platform_subscription_events', [
            'workspace_id' => $workspace->id,
            'event_type' => 'demo_reset',
        ]);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'workspace.demo_reset',
            'subject_id' => $workspace->id,
        ]);
    }

    public function test_demo_reset_command_only_resets_scheduled_due_workspaces(): void
    {
        $due = Workspace::create([
            'name' => 'Due Demo',
            'plan' => 'demo',
            'subscription_status' => 'demo',
            'demo_reset_frequency' => 'daily',
            'demo_last_reset_at' => now()->subDays(2),
        ]);
        $manual = Workspace::create([
            'name' => 'Manual Demo',
            'plan' => 'demo',
            'subscription_status' => 'demo',
            'demo_reset_frequency' => 'manual',
        ]);

        Project::create(['workspace_id' => $due->id, 'name' => 'Due Project']);
        Project::create(['workspace_id' => $manual->id, 'name' => 'Manual Project']);

        $this->artisan('demo:reset-workspaces')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('projects', ['workspace_id' => $due->id, 'name' => 'Due Project']);
        $this->assertDatabaseHas('projects', ['workspace_id' => $manual->id, 'name' => 'Manual Project']);
        $this->assertNotNull($due->refresh()->demo_last_reset_at);
        $this->assertNull($manual->refresh()->demo_last_reset_at);
        $this->assertDatabaseHas('platform_subscription_events', [
            'workspace_id' => $due->id,
            'event_type' => 'demo_reset',
        ]);
    }

    public function test_activate_table_action_restores_access_for_expired_suspended_workspace(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $workspace = Workspace::create([
            'name' => 'Blocked Active Workspace',
            'subscription_status' => 'expired',
            'trial_ends_at' => now()->subDays(20),
            'subscription_ends_at' => now()->subDays(10),
            'is_suspended' => true,
        ]);

        $this->assertFalse(WorkspaceAccess::isSubscriptionActive($workspace));
        $this->assertTrue(WorkspaceAccess::isReadOnly($workspace));

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->callTableAction('activate', $workspace)
            ->assertHasNoTableActionErrors();

        $workspace->refresh();
        $this->assertSame('active', $workspace->subscription_status);
        $this->assertFalse($workspace->is_suspended);
        $this->assertNull($workspace->trial_ends_at);
        $this->assertNull($workspace->subscription_ends_at);
        $this->assertTrue(WorkspaceAccess::isSubscriptionActive($workspace));
        $this->assertFalse(WorkspaceAccess::isReadOnly($workspace));
    }

    public function test_admin_can_suspend_workspace_with_structured_reason(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $workspace = Workspace::create([
            'name' => 'Billing Problem Workspace',
            'subscription_status' => 'active',
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->callTableAction('suspend', $workspace, data: [
                'suspension_category' => 'billing_issue',
                'suspension_reason' => 'Invoice unpaid after repeated reminders.',
                'confirmation' => 'SUSPEND',
            ])
            ->assertHasNoTableActionErrors();

        $workspace->refresh();
        $this->assertSame('suspended', $workspace->subscription_status);
        $this->assertTrue($workspace->is_suspended);
        $this->assertSame('billing_issue', $workspace->suspension_category);
        $this->assertSame('Invoice unpaid after repeated reminders.', $workspace->suspension_reason);
        $this->assertSame($admin->id, $workspace->suspended_by);
        $this->assertNotNull($workspace->suspended_at);
        $this->assertFalse(WorkspaceAccess::isSubscriptionActive($workspace));
        $this->assertTrue(WorkspaceAccess::isReadOnly($workspace));
        $this->assertDatabaseHas('platform_subscription_events', [
            'workspace_id' => $workspace->id,
            'actor_id' => $admin->id,
            'event_type' => 'suspended',
        ]);
    }

    public function test_admin_can_access_subscription_detail_action_from_workspace_table(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $workspace = Workspace::create([
            'name' => 'Subscription Detail Workspace',
            'plan' => 'writer',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(3),
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->assertTableActionVisible('viewSubscription', $workspace)
            ->assertSee('Trial ending soon')
            ->assertSee('View subscription');
    }

    public function test_platform_subscriptions_command_center_lists_access_statuses(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        User::factory()->create([
            'name' => 'Active Client Account',
            'email' => 'active.client@example.test',
            'plan' => 'writer',
            'subscription_status' => 'active',
        ]);
        User::factory()->create([
            'name' => 'Trial Client Account',
            'email' => 'trial.client@example.test',
            'plan' => 'free',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(3),
        ]);
        User::factory()->create([
            'name' => 'Demo Client Account',
            'email' => 'demo.client@example.test',
            'plan' => 'demo',
            'subscription_status' => 'demo',
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        $this->get(PlatformSubscriptionResource::getUrl(panel: 'platform'))
            ->assertOk()
            ->assertSee('Subscription command center')
            ->assertSee('Active subscriptions')
            ->assertSee('Trials')
            ->assertSee('Demo accounts')
            ->assertSee('Needs attention')
            ->assertSee('Next action')
            ->assertSee('Extend or convert')
            ->assertSee('Reset when needed')
            ->assertSee('Active Client Account')
            ->assertSee('Trial Client Account')
            ->assertSee('Demo Client Account')
            ->assertDontSee('wire:poll.5s', false);
    }

    public function test_platform_plans_page_shows_read_only_entitlements(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        $this->get(PlatformPlans::getUrl(panel: 'platform'))
            ->assertOk()
            ->assertSee('Plans &amp; entitlements', false)
            ->assertSee('Read-only catalogue')
            ->assertSee('Free')
            ->assertSee('Writer')
            ->assertSee('Writer Pro')
            ->assertSee('Demo')
            ->assertSee('Public')
            ->assertSee('Internal')
            ->assertSee('Recommended')
            ->assertSee('Documents / month')
            ->assertSee('Individual Support Calculator')
            ->assertDontSee('wire:poll.5s', false);
    }

    public function test_platform_activity_center_shows_audited_operations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN, 'name' => 'Activity Admin']);
        $workspace = Workspace::create(['name' => 'Activity Workspace']);

        PlatformAuditLog::create([
            'actor_id' => $admin->id,
            'subject_type' => $workspace->getMorphClass(),
            'subject_id' => $workspace->id,
            'action' => 'workspace.billing_updated',
            'description' => 'Updated billing readiness for Activity Workspace',
            'metadata' => ['billing_provider' => 'stripe', 'billing_reference' => 'MC-REF'],
            'ip_address' => '127.0.0.1',
        ]);
        PlatformAuditLog::create([
            'actor_id' => $admin->id,
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'action' => 'impersonation.started',
            'description' => 'Started impersonation for support.',
            'metadata' => ['reason' => 'Debugging ticket'],
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        $this->get(PlatformActivityResource::getUrl(panel: 'platform'))
            ->assertOk()
            ->assertSee('Activity center')
            ->assertSee('Actions today')
            ->assertSee('Impersonations')
            ->assertSee('Account changes')
            ->assertSee('Subscription changes')
            ->assertSee('Activity Admin')
            ->assertSee('Workspace Billing Updated')
            ->assertSee('Updated billing readiness for Activity Workspace')
            ->assertSee('Billing Provider')
            ->assertSee('MC-REF')
            ->assertDontSee('wire:poll.5s', false);
    }

    public function test_platform_activity_detail_shows_audit_context(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
            'name' => 'Audit Inspector',
            'email' => 'audit.inspector@example.test',
        ]);
        $workspace = Workspace::create(['name' => 'Detailed Audit Workspace']);

        $log = PlatformAuditLog::create([
            'actor_id' => $admin->id,
            'subject_type' => $workspace->getMorphClass(),
            'subject_id' => $workspace->id,
            'action' => 'workspace.manual_access_granted',
            'description' => 'Granted manual access for Detailed Audit Workspace',
            'metadata' => [
                'plan' => 'writer_pro',
                'reason' => 'Commercial exception approved by owner.',
                'unblocked' => true,
                'limits' => ['projects' => 10],
            ],
            'ip_address' => '10.10.10.10',
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        $this->get(PlatformActivityResource::getUrl('view', ['record' => $log], panel: 'platform'))
            ->assertOk()
            ->assertSee('Workspace Manual Access Granted')
            ->assertSee('Audit event #'.$log->id)
            ->assertSee('Audit Inspector')
            ->assertSee('audit.inspector@example.test')
            ->assertSee('Detailed Audit Workspace')
            ->assertSee('Granted manual access for Detailed Audit Workspace')
            ->assertSee('10.10.10.10')
            ->assertSee('Commercial exception approved by owner.')
            ->assertSee('projects')
            ->assertSee('Yes');
    }

    public function test_platform_permissions_and_health_pages_are_available_to_admins(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        $this->get(PlatformPermissions::getUrl(panel: 'platform'))
            ->assertOk()
            ->assertSee('Permissions matrix')
            ->assertSee('Grant manual access outside subscription rules')
            ->assertSee('Permanently delete accounts');

        $this->get(PlatformHealth::getUrl(panel: 'platform'))
            ->assertOk()
            ->assertSee('System health')
            ->assertSee('Database')
            ->assertSee('Failed jobs')
            ->assertSee('Mail');
    }

    public function test_platform_subscriptions_command_center_has_quick_view_tabs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        User::factory()->create([
            'name' => 'Visible Trial Account',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(4),
        ]);
        User::factory()->create([
            'name' => 'Visible Demo Account',
            'plan' => 'demo',
            'subscription_status' => 'demo',
        ]);
        User::factory()->create([
            'name' => 'Visible Manual Account',
            'subscription_status' => 'active',
            'access_override_reason' => 'Founder pilot.',
        ]);
        User::factory()->create([
            'name' => 'Visible Blocked Account',
            'subscription_status' => 'expired',
            'subscription_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformSubscriptions::class)
            ->assertSee('All')
            ->assertSee('Needs attention')
            ->assertSee('Trial')
            ->assertSee('Demo')
            ->assertSee('Manual access')
            ->assertSee('Expired / suspended')
            ->set('activeTab', 'trial')
            ->assertSee('Visible Trial Account')
            ->assertDontSee('Visible Demo Account')
            ->set('activeTab', 'demo')
            ->assertSee('Visible Demo Account')
            ->assertDontSee('Visible Trial Account')
            ->set('activeTab', 'manual_access')
            ->assertSee('Visible Manual Account')
            ->assertDontSee('Visible Trial Account')
            ->set('activeTab', 'blocked')
            ->assertSee('Visible Blocked Account')
            ->assertDontSee('Visible Demo Account');
    }

    public function test_platform_subscriptions_command_center_shows_latest_subscription_event(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN, 'name' => 'Admin Operator']);
        $account = User::factory()->create([
            'name' => 'Eventful Subscription Account',
            'email' => 'eventful.subscription@example.test',
            'subscription_status' => 'active',
        ]);
        $workspace = app(\App\Services\AccountWorkspaceService::class)->ensureFor($account);
        $account->subscriptionEvents()->create([
            'workspace_id' => $workspace->id,
            'actor_id' => null,
            'event_type' => 'trial_updated',
            'summary' => 'Old trial event.',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        $account->subscriptionEvents()->create([
            'workspace_id' => $workspace->id,
            'actor_id' => $admin->id,
            'event_type' => 'activated',
            'summary' => 'Account activated and access restored.',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformSubscriptions::class)
            ->assertSee('Last event')
            ->assertSee('Activated')
            ->assertSee('Admin Operator')
            ->assertSee('Account activated and access restored.')
            ->assertDontSee('Old trial event.');
    }

    public function test_admin_can_edit_billing_metadata_from_subscriptions_command_center(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $account = User::factory()->create([
            'name' => 'Subscription Billing Account',
            'email' => 'billing.account@example.test',
            'subscription_status' => 'active',
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformSubscriptions::class)
            ->assertTableActionVisible('editBilling', $account)
            ->callTableAction('editBilling', $account, data: [
                'billing_interval' => 'monthly',
                'billing_amount' => 99,
                'billing_currency' => 'ron',
                'billing_reference' => 'MC-MONTHLY-99',
                'billing_provider' => 'manual',
                'billing_provider_customer_id' => 'manual-client-1',
                'billing_provider_subscription_id' => 'manual-sub-1',
            ])
            ->assertHasNoTableActionErrors();

        $account->refresh();
        $this->assertSame('monthly', $account->billing_interval);
        $this->assertSame('99.00', $account->billing_amount);
        $this->assertSame('RON', $account->billing_currency);
        $this->assertSame('MC-MONTHLY-99', $account->billing_reference);
        $this->assertSame('manual', $account->billing_provider);
        $this->assertDatabaseHas('platform_subscription_events', [
            'user_id' => $account->id,
            'actor_id' => $admin->id,
            'event_type' => 'billing_updated',
            'summary' => 'Billing readiness details updated.',
        ]);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'account.billing_updated',
            'subject_id' => $account->id,
        ]);
    }

    public function test_subscription_alert_command_notifies_platform_staff_once(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $suspendedAdmin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
            'is_suspended' => true,
        ]);
        $account = User::factory()->create([
            'name' => 'Trial Alert Account',
            'email' => 'trial.alert@example.test',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(3),
        ]);

        $this->artisan('subscriptions:send-alerts')
            ->expectsOutput('1 subscription alert queued.')
            ->assertSuccessful();

        $account->refresh();
        $this->assertNotNull($account->trial_ending_alerted_at);
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(0, $suspendedAdmin->notifications()->count());
        $this->assertSame('Trial ending soon', $owner->notifications()->first()->data['title']);
        $this->assertStringContainsString('Trial Alert Account', $owner->notifications()->first()->data['body']);

        $this->artisan('subscriptions:send-alerts')
            ->expectsOutput('0 subscription alerts queued.')
            ->assertSuccessful();

        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_subscription_alert_command_covers_expired_manual_and_demo_cases(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);

        User::factory()->create([
            'name' => 'Expired Paid Account',
            'subscription_status' => 'expired',
            'subscription_ends_at' => now()->subDay(),
        ]);
        User::factory()->create([
            'name' => 'Manual Access Account',
            'subscription_status' => 'active',
            'access_override_reason' => 'Pilot access.',
            'access_override_ends_at' => now()->addDays(2),
        ]);
        User::factory()->create([
            'name' => 'Stale Demo Account',
            'plan' => 'demo',
            'subscription_status' => 'demo',
            'demo_reset_frequency' => 'weekly',
            'demo_last_reset_at' => now()->subDays(10),
        ]);

        $this->artisan('subscriptions:send-alerts')
            ->expectsOutput('3 subscription alerts queued.')
            ->assertSuccessful();

        $titles = $owner->notifications()->get()->pluck('data.title')->all();
        $this->assertEqualsCanonicalizing([
            'Subscription expired',
            'Manual access ending soon',
            'Demo reset may be stale',
        ], $titles);
    }

    public function test_admin_can_activate_workspace_from_subscriptions_command_center(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $account = User::factory()->create([
            'name' => 'Expired Subscription Account',
            'email' => 'expired.subscription@example.test',
            'subscription_status' => 'expired',
            'subscription_ends_at' => now()->subDays(5),
            'is_suspended' => true,
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformSubscriptions::class)
            ->callTableAction('activate', $account)
            ->assertHasNoTableActionErrors();

        $account->refresh();
        $this->assertSame('active', $account->subscription_status);
        $this->assertFalse($account->is_suspended);
        $this->assertNull($account->subscription_ends_at);
        $this->assertDatabaseHas('platform_subscription_events', [
            'user_id' => $account->id,
            'actor_id' => $admin->id,
            'event_type' => 'activated',
        ]);
    }

    public function test_only_owner_can_grant_manual_access_from_workspace_table(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $workspace = Workspace::create([
            'name' => 'Pilot Access Workspace',
            'subscription_status' => 'expired',
            'subscription_ends_at' => now()->subDays(20),
            'is_suspended' => true,
            'suspension_category' => 'manual_review',
            'suspension_reason' => 'Waiting for owner approval.',
            'suspended_at' => now()->subDay(),
            'suspended_by' => $admin->id,
        ]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->assertTableActionHidden('grantManualAccess', $workspace);

        $this->actingAs($owner);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformWorkspaces::class)
            ->callTableAction('grantManualAccess', $workspace, data: [
                'plan' => 'writer_pro',
                'access_override_ends_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
                'access_override_reason' => 'Founder granted a pilot workspace for onboarding.',
                'feature_flags' => [PlanCatalog::MODULE_PROJECTS, PlanCatalog::MODULE_WRITING],
            ])
            ->assertHasNoTableActionErrors();

        $workspace->refresh();
        $this->assertSame('writer_pro', $workspace->plan);
        $this->assertSame(PlanCatalog::defaultLimits('writer_pro'), $workspace->plan_limits);
        $this->assertSame('active', $workspace->subscription_status);
        $this->assertFalse($workspace->is_suspended);
        $this->assertNull($workspace->suspension_category);
        $this->assertNull($workspace->suspension_reason);
        $this->assertSame($owner->id, $workspace->access_override_granted_by);
        $this->assertSame('Founder granted a pilot workspace for onboarding.', $workspace->access_override_reason);
        $this->assertContains(PlanCatalog::MODULE_PROJECTS, $workspace->feature_flags);
        $this->assertContains(PlanCatalog::MODULE_WRITING, $workspace->feature_flags);
        $this->assertTrue(WorkspaceAccess::isSubscriptionActive($workspace));
        $this->assertFalse(WorkspaceAccess::isReadOnly($workspace));
        $this->assertDatabaseHas('platform_subscription_events', [
            'workspace_id' => $workspace->id,
            'actor_id' => $owner->id,
            'event_type' => 'manual_access_granted',
        ]);
    }

    public function test_account_feature_flags_can_disable_a_module(): void
    {
        $workspace = Workspace::create([
            'name' => 'Feature Workspace',
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'feature_flags' => array_values(array_diff(array_keys(PlanCatalog::moduleOptions()), [PlanCatalog::MODULE_CURRENCIES])),
        ]);
        $workspace->users()->attach($user, ['role' => 'admin']);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        $this->assertFalse(ManageCurrencies::canAccess());
        $this->assertFalse(AccountAccess::moduleEnabled($user, PlanCatalog::MODULE_CURRENCIES));
    }

    public function test_workspace_project_limit_blocks_new_project_creation(): void
    {
        $workspace = Workspace::create([
            'name' => 'Limited Workspace',
            'plan_limits' => ['projects' => 1],
        ]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $workspace->users()->attach($user, ['role' => 'owner']);
        \App\Models\Project::create(['workspace_id' => $workspace->id, 'name' => 'Existing Project']);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        $this->assertFalse($user->can('create', \App\Models\Project::class));
    }

    public function test_expired_workspace_is_read_only_but_owner_override_restores_access(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $workspace = Workspace::create([
            'name' => 'Expired Workspace',
            'subscription_status' => 'expired',
            'subscription_ends_at' => now()->subDays(10),
        ]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $workspace->users()->attach($user, ['role' => 'owner']);

        $this->assertTrue(WorkspaceAccess::isReadOnly($workspace));
        $this->assertFalse(WorkspaceAccess::isSubscriptionActive($workspace));
        $this->assertFalse($workspace->canBeManagedBy($user));

        $workspace->update([
            'access_override_reason' => 'Owner granted temporary access for onboarding.',
            'access_override_ends_at' => now()->addDays(14),
            'access_override_granted_by' => $owner->id,
        ]);

        $workspace->refresh();
        $this->assertTrue(WorkspaceAccess::hasOwnerGrantedAccess($workspace));
        $this->assertTrue(WorkspaceAccess::isSubscriptionActive($workspace));
        $this->assertFalse(WorkspaceAccess::isReadOnly($workspace));
        $this->assertTrue($workspace->canBeManagedBy($user));
    }

    public function test_only_platform_owner_can_save_manual_access_override_fields(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $workspace = Workspace::create(['name' => 'Manual Access Workspace']);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(EditPlatformWorkspace::class, ['record' => $workspace->id])
            ->fillForm([
                'name' => 'Manual Access Workspace',
                'plan' => 'free',
                'subscription_status' => 'active',
                'is_suspended' => false,
                'is_internal' => false,
                'access_override_reason' => 'Admin should not be able to set this.',
                'access_override_ends_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $workspace->refresh();
        $this->assertNull($workspace->access_override_reason);

        $this->actingAs($owner);
        $this->usePlatformPanel();

        Livewire::test(EditPlatformWorkspace::class, ['record' => $workspace->id])
            ->fillForm([
                'name' => 'Manual Access Workspace',
                'plan' => 'free',
                'subscription_status' => 'active',
                'is_suspended' => false,
                'is_internal' => false,
                'access_override_reason' => 'Owner granted pilot access.',
                'access_override_ends_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $workspace->refresh();
        $this->assertSame('Owner granted pilot access.', $workspace->access_override_reason);
        $this->assertSame($owner->id, $workspace->access_override_granted_by);
    }

    public function test_suspended_account_is_visible_and_cannot_access_panels(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $user = User::factory()->create(['role' => User::ROLE_USER, 'is_suspended' => true]);

        $this->actingAs($owner);
        $this->usePlatformPanel();

        Livewire::test(\App\Filament\Resources\PlatformUsers\Pages\EditPlatformUser::class, ['record' => $user->id])
            ->assertSee('Suspended account')
            ->assertFormSet(['is_suspended' => true]);

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('platform')));
    }

    public function test_suspended_platform_admin_can_sign_in_but_is_sent_to_suspended_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
            'is_suspended' => true,
        ]);

        $this->useAdminPanel();

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $admin->email,
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('account.suspended'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->refresh()->last_login_at);

        $this->get(Dashboard::getUrl(panel: 'platform'))
            ->assertRedirect(route('account.suspended'));
    }

    public function test_platform_admin_can_sign_in_from_client_login_and_is_redirected_to_platform_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);

        $this->useAdminPanel();

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $admin->email,
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('filament.platform.pages.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->refresh()->last_login_at);

        $this->get('/app')
            ->assertRedirect(route('filament.platform.pages.dashboard'));
    }

    public function test_login_exposes_password_reset_and_sends_reset_email(): void
    {
        NotificationFacade::fake();

        $user = User::factory()->create();

        $this->useAdminPanel();

        $this->get(route('filament.admin.auth.login'))
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee(route('filament.admin.auth.password-reset.request'), escape: false);

        $this->get(route('filament.admin.auth.password-reset.request'))
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee('Send email');

        Livewire::test(RequestPasswordReset::class)
            ->fillForm([
                'email' => $user->email,
            ])
            ->call('request')
            ->assertHasNoFormErrors();

        NotificationFacade::assertSentTo($user, ResetPassword::class);
    }

    public function test_platform_login_redirects_to_single_login_page(): void
    {
        $this->get(route('filament.platform.auth.login'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_platform_resources_are_not_registered_on_client_workspace_panel(): void
    {
        $routeNames = collect(Route::getRoutes())
            ->map(fn ($route): ?string => $route->getName())
            ->filter()
            ->values();

        $this->assertTrue($routeNames->contains('filament.platform.resources.platform-users.index'));
        $this->assertTrue($routeNames->contains('filament.platform.pages.platform-plans'));
        $this->assertTrue($routeNames->contains('filament.platform.resources.platform-activities.index'));
        $this->assertTrue($routeNames->contains('filament.platform.resources.platform-subscriptions.index'));
        $this->assertTrue($routeNames->contains('filament.platform.resources.platform-workspaces.index'));
        $this->assertTrue($routeNames->contains('filament.platform.resources.platform-announcements.index'));
        $this->assertTrue($routeNames->contains('filament.platform.resources.public-block-reports.index'));

        $this->assertFalse($routeNames->contains('filament.admin.resources.platform-users.index'));
        $this->assertFalse($routeNames->contains('filament.admin.pages.platform-plans'));
        $this->assertFalse($routeNames->contains('filament.admin.resources.platform-activities.index'));
        $this->assertFalse($routeNames->contains('filament.admin.resources.platform-subscriptions.index'));
        $this->assertFalse($routeNames->contains('filament.admin.resources.platform-workspaces.index'));
        $this->assertFalse($routeNames->contains('filament.admin.resources.platform-announcements.index'));
        $this->assertFalse($routeNames->contains('filament.admin.resources.platform-audit-logs.index'));
        $this->assertFalse($routeNames->contains('filament.admin.resources.public-block-reports.index'));
    }

    public function test_all_filament_logouts_redirect_to_single_login_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $this->actingAs($admin);

        $this->post(route('filament.platform.auth.logout'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();

        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $this->post(route('filament.admin.auth.logout'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
    }

    public function test_suspended_platform_admin_cannot_open_platform_panel(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
            'is_suspended' => true,
            'suspension_category' => 'security_review',
            'suspension_reason' => 'Suspicious login activity.',
        ]);

        $this->actingAs($admin);

        $this->get(Dashboard::getUrl(panel: 'platform'))
            ->assertRedirect(route('account.suspended'));

        $this->get(route('account.suspended'))
            ->assertOk()
            ->assertSee('Your MobilityCloud account is currently suspended')
            ->assertSee('Security review')
            ->assertSee('contact@mobilitycloud.eu')
            ->assertSee($admin->email);

        $this->get(route('account.suspended.logout'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
    }

    public function test_suspended_workspace_user_is_redirected_to_suspended_account_page(): void
    {
        $workspace = Workspace::create(['name' => 'Client Workspace']);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_suspended' => true,
        ]);
        $user->workspaces()->attach($workspace, ['role' => 'owner']);

        $this->actingAs($user);

        $this->get(Dashboard::getUrl(panel: 'admin', tenant: $workspace))
            ->assertRedirect(route('account.suspended'));
    }

    public function test_suspended_account_page_is_available_after_session_expires(): void
    {
        $this->get(route('account.suspended'))
            ->assertOk()
            ->assertSee('Your MobilityCloud account is currently suspended')
            ->assertSee('contact@mobilitycloud.eu');

        $this->get(route('account.suspended.logout'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_admin_can_reset_user_password_from_accounts_table(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($owner);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformUsers::class)
            ->callTableAction('resetPassword', $user, data: [
                'password' => 'new-secure-password',
                'must_change_password' => true,
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'account.password_reset',
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_can_suspend_and_reactivate_user_with_structured_reason(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformUsers::class)
            ->callTableAction('suspend', $user, data: [
                'suspension_category' => 'security_review',
                'suspension_reason' => 'Unusual account activity reported by support.',
                'confirmation' => 'SUSPEND',
            ])
            ->assertHasNoTableActionErrors();

        $user->refresh();
        $this->assertTrue($user->is_suspended);
        $this->assertSame('security_review', $user->suspension_category);
        $this->assertSame('Unusual account activity reported by support.', $user->suspension_reason);
        $this->assertSame($admin->id, $user->suspended_by);
        $this->assertNotNull($user->suspended_at);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'account.suspended',
            'subject_id' => $user->id,
        ]);

        Livewire::test(ListPlatformUsers::class)
            ->callTableAction('reactivate', $user)
            ->assertHasNoTableActionErrors();

        $user->refresh();
        $this->assertFalse($user->is_suspended);
        $this->assertNull($user->suspension_category);
        $this->assertNull($user->suspension_reason);
        $this->assertNull($user->suspended_by);
        $this->assertNull($user->suspended_at);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'account.reactivated',
            'subject_id' => $user->id,
        ]);
    }

    public function test_platform_owner_can_archive_and_restore_regular_user_account(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($owner);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformUsers::class)
            ->callTableAction('archive', $user, data: [
                'reason' => 'Client account closed but may return later.',
            ])
            ->assertHasNoTableActionErrors();

        $user->refresh();
        $this->assertNotNull($user->archived_at);
        $this->assertSame($owner->id, $user->archived_by);
        $this->assertSame('Client account closed but may return later.', $user->archived_reason);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'account.archived',
            'subject_id' => $user->id,
        ]);

        Livewire::test(ListPlatformUsers::class)
            ->callTableAction('restore', $user)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'account.restored',
            'subject_id' => $user->id,
        ]);
    }

    public function test_platform_owner_can_permanently_delete_regular_user_account(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $workspace = Workspace::create(['name' => 'Delete User Workspace']);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $workspace->users()->attach($user, ['role' => 'member']);
        $user->supportNotes()->create([
            'author_id' => $owner->id,
            'category' => 'support',
            'body' => 'Account deletion requested by client.',
        ]);

        $this->actingAs($owner);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformUsers::class)
            ->callTableAction('deletePermanently', $user, data: [
                'confirmation_email' => $user->email,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('workspace_user', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('platform_support_notes', ['user_id' => $user->id]);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'account.deleted_permanently',
            'subject_id' => $user->id,
        ]);
    }

    public function test_platform_admin_cannot_permanently_delete_accounts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        $this->assertFalse(PlatformUserResource::canPermanentlyDeleteAccount($user));

        Livewire::test(ListPlatformUsers::class)
            ->assertTableActionHidden('deletePermanently', $user);
    }

    public function test_platform_owner_cannot_delete_self_or_last_platform_owner(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $this->actingAs($owner);
        $this->usePlatformPanel();

        $this->assertFalse(PlatformUserResource::canPermanentlyDeleteAccount($owner));

        Livewire::test(ListPlatformUsers::class)
            ->assertTableActionHidden('deletePermanently', $owner);
    }

    public function test_permanent_delete_requires_exact_email_confirmation(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($owner);
        $this->usePlatformPanel();

        Livewire::test(ListPlatformUsers::class)
            ->callTableAction('deletePermanently', $user, data: [
                'confirmation_email' => 'wrong@example.test',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('platform_audit_logs', [
            'action' => 'account.deleted_permanently',
            'subject_id' => $user->id,
        ]);
    }

    public function test_platform_admin_cannot_modify_platform_admin_or_owner_accounts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $otherAdmin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        $this->assertFalse(PlatformUserResource::canManageAccount($otherAdmin));
        $this->assertFalse(PlatformUserResource::canManageAccount($owner));

        $this->get(PlatformUserResource::getUrl('edit', ['record' => $otherAdmin], panel: 'platform'))
            ->assertForbidden();

        $this->get(PlatformUserResource::getUrl('edit', ['record' => $owner], panel: 'platform'))
            ->assertForbidden();
    }

    public function test_platform_owner_can_modify_platform_admin_accounts(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($owner);
        $this->usePlatformPanel();

        $this->assertTrue(PlatformUserResource::canManageAccount($admin));

        $this->get(PlatformUserResource::getUrl('edit', ['record' => $admin], panel: 'platform'))
            ->assertOk();
    }

    public function test_platform_admin_can_impersonate_user_and_exit_impersonation(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
            'password' => Hash::make('admin-password'),
        ]);
        $workspace = Workspace::create(['name' => 'Client Workspace']);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'password' => Hash::make('user-password'),
        ]);
        $user->workspaces()->attach($workspace, ['role' => 'owner']);
        $user->update(['current_workspace_id' => $workspace->id]);

        $this->actingAs($admin);
        session(['password_hash_web' => $admin->getAuthPassword()]);
        session(['impersonation_reason_'.$user->id => 'Debugging support ticket #123']);
        $this->usePlatformPanel();

        $this->get(route('platform.impersonation.start', $user))
            ->assertRedirect(Dashboard::getUrl(panel: 'admin', tenant: $workspace));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($admin->getAuthPassword(), session('password_hash_web'));
        $this->assertSame($admin->id, session('impersonator_id'));
        $this->assertSame($user->id, session('impersonated_user_id'));
        $this->assertSame('Debugging support ticket #123', session('impersonation_reason'));

        $startedLog = PlatformAuditLog::query()
            ->where('action', 'impersonation.started')
            ->where('subject_id', $user->id)
            ->firstOrFail();
        $this->assertSame('Debugging support ticket #123', $startedLog->metadata['reason']);

        $this->get(route('platform.impersonation.stop'))
            ->assertRedirect(route('filament.platform.pages.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($user->getAuthPassword(), session('password_hash_web'));
        $this->assertNull(session('impersonator_id'));
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'impersonation.ended',
            'subject_id' => $user->id,
        ]);
    }

    public function test_impersonation_requires_a_reason(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        $this->get(route('platform.impersonation.start', $user))
            ->assertRedirect(PlatformUserResource::getUrl(panel: 'platform'));

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseMissing('platform_audit_logs', [
            'action' => 'impersonation.started',
            'subject_id' => $user->id,
        ]);
    }

    public function test_platform_support_notes_are_structured_internal_timeline_entries(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $note = $user->supportNotes()->create([
            'author_id' => $admin->id,
            'category' => 'billing',
            'body' => 'Client asked for invoice details before renewal.',
            'is_pinned' => true,
        ]);

        $this->assertInstanceOf(PlatformSupportNote::class, $note);
        $this->assertSame('billing', $note->category);
        $this->assertTrue($note->is_pinned);
        $this->assertSame($admin->id, $note->author->id);
    }

    public function test_admin_can_add_support_note_from_account_timeline(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(SupportNotesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => EditPlatformUser::class,
        ])
            ->callTableAction('create', data: [
                'category' => 'urgent',
                'is_pinned' => true,
                'body' => 'Needs urgent billing follow-up.',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('platform_support_notes', [
            'user_id' => $user->id,
            'author_id' => $admin->id,
            'category' => 'urgent',
            'is_pinned' => true,
        ]);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'support_note.created',
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_can_add_workspace_note_from_workspace_record(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $workspace = Workspace::create(['name' => 'Workspace With Notes']);

        $note = $workspace->platformNotes()->create([
            'author_id' => $admin->id,
            'category' => 'commercial',
            'body' => 'Manual access promised during onboarding.',
            'is_pinned' => true,
        ]);

        $this->assertInstanceOf(PlatformWorkspaceNote::class, $note);
        $this->assertSame('commercial', $note->category);
        $this->assertTrue($note->is_pinned);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(WorkspaceNotesRelationManager::class, [
            'ownerRecord' => $workspace,
            'pageClass' => EditPlatformWorkspace::class,
        ])
            ->callTableAction('create', data: [
                'category' => 'billing',
                'is_pinned' => false,
                'body' => 'Billing contact asked for yearly invoice.',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('platform_workspace_notes', [
            'workspace_id' => $workspace->id,
            'author_id' => $admin->id,
            'category' => 'billing',
        ]);
        $this->assertDatabaseHas('platform_audit_logs', [
            'action' => 'workspace_note.created',
            'subject_id' => $workspace->id,
        ]);
    }

    public function test_admin_can_add_manual_subscription_timeline_note(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $workspace = Workspace::create(['name' => 'Client Workspace']);

        $this->actingAs($admin);
        $this->usePlatformPanel();

        Livewire::test(SubscriptionEventsRelationManager::class, [
            'ownerRecord' => $workspace,
            'pageClass' => EditPlatformWorkspace::class,
        ])
            ->callTableAction('create', data: [
                'event_type' => 'manual_note',
                'summary' => 'Client requested annual billing.',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('platform_subscription_events', [
            'workspace_id' => $workspace->id,
            'actor_id' => $admin->id,
            'event_type' => 'manual_note',
            'summary' => 'Client requested annual billing.',
        ]);
    }

    public function test_platform_admin_can_create_header_announcement_visible_in_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $this->actingAs($admin);
        $this->usePlatformPanel();

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

        $this->get(Dashboard::getUrl(panel: 'platform'))
            ->assertOk()
            ->assertSee('Maintenance tonight')
            ->assertSee('Document generation will be unavailable');
    }

    public function test_platform_admin_does_not_need_a_workspace_to_open_platform_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin);

        $this->assertSame(0, $admin->workspaces()->count());
        $this->get(Dashboard::getUrl(panel: 'platform'))
            ->assertOk()
            ->assertSee('Platform administration')
            ->assertSee('Platform admin area')
            ->assertSee('Platform admin')
            ->assertSee('Needs attention')
            ->assertSee('Platform management')
            ->assertSee('Billing &amp; access', false)
            ->assertSee('Audit &amp; operations', false)
            ->assertSee('Activity center')
            ->assertSee('System health');
    }

    public function test_platform_account_settings_hide_workspace_specific_controls(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin);

        $this->get(AccountSettings::getUrl(panel: 'platform'))
            ->assertOk()
            ->assertSee('Platform admin preferences')
            ->assertSee('Platform overview')
            ->assertSee('Users')
            ->assertSee('Subscriptions')
            ->assertDontSee('Your workspaces')
            ->assertDontSee('Current workspace')
            ->assertDontSee('Update plan')
            ->assertDontSee('Task overdue')
            ->assertDontSee('New task assigned')
            ->assertDontSee('Workspace overview')
            ->assertDontSee('My Tasks');
    }

    public function test_platform_owner_role_badge_is_visible_in_platform_panel(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);

        $this->actingAs($owner);

        $this->get(Dashboard::getUrl(panel: 'platform'))
            ->assertOk()
            ->assertSee('Platform owner');
    }

    public function test_platform_admin_is_not_sent_to_client_workspace_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $client = User::factory()->create(['role' => User::ROLE_USER]);

        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('platform')));
        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('admin')));
        $this->assertFalse($client->canAccessPanel(Filament::getPanel('platform')));
        $this->assertTrue($client->canAccessPanel(Filament::getPanel('admin')));

        $this->actingAs($admin);

        $this->get('/app')
            ->assertRedirect(route('filament.platform.pages.dashboard'));
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
        $writerProUser = User::factory()->create(['plan' => 'writer_pro']);
        $freeUser = User::factory()->create(['plan' => 'free']);

        $this->assertTrue($announcement->isVisibleFor($writerProUser, null));
        $this->assertFalse($announcement->isVisibleFor($freeUser, null));
    }

    private function usePlatformPanel(): void
    {
        Filament::setCurrentPanel('platform');
    }

    private function useAdminPanel(): void
    {
        Filament::setCurrentPanel('admin');
    }

}
