<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Filament\Resources\PlatformProjectPayments\Pages\ListPlatformProjectPayments;
use App\Filament\Resources\PlatformProjectPayments\PlatformProjectPaymentResource;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformProjectPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_manage_approved_project_payment_and_unlock_modules(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $client = User::factory()->create([
            'billing_name' => 'Scoala de Jocuri',
            'billing_vat' => 'RO123456',
            'billing_country' => 'Romania',
            'billing_address' => 'Baia Mare, Romania',
        ]);

        $project = Project::create([
            'owner_id' => $client->id,
            'name' => 'Approved Grant Project',
            'status' => ProjectStatus::PaymentOverdue->value,
            'approved_budget' => 15000,
            'approved_grant_amount' => 15000,
            'approved_declared_at' => now()->subDays(10),
            'activation_fee_amount' => 150,
            'invoice_status' => Project::INVOICE_OVERDUE,
            'invoice_due_at' => now()->subDay(),
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel('platform');

        $this->assertTrue(PlatformProjectPaymentResource::canAccess());
        $this->assertTrue($project->fresh()->hasPaymentOverdue());
        $this->assertFalse($project->fresh()->implementationModulesAvailable());

        Livewire::test(ListPlatformProjectPayments::class)
            ->assertSee('Approved Grant Project')
            ->assertSee('Scoala de Jocuri')
            ->assertTableActionVisible('markPaid', $project)
            ->callTableAction('markPaid', $project);

        $project->refresh();

        $this->assertSame(Project::INVOICE_PAID, $project->invoice_status);
        $this->assertSame(ProjectStatus::Active->value, $project->status);
        $this->assertNotNull($project->payment_confirmed_at);
        $this->assertTrue($project->implementationModulesAvailable());
    }

    public function test_regular_users_cannot_access_project_payment_queue(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel('platform');

        $this->assertFalse(PlatformProjectPaymentResource::canAccess());
    }

    public function test_unlimited_accounts_are_not_listed_as_project_payments(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $unlimited = User::factory()->create([
            'plan' => 'unlimited',
            'feature_flags' => ['unlimited'],
            'plan_limits' => ['unlimited' => true],
            'billing_name' => null,
            'billing_country' => null,
            'billing_address' => null,
        ]);

        $project = Project::create([
            'owner_id' => $unlimited->id,
            'name' => 'Unlimited Project With Legacy Invoice',
            'status' => ProjectStatus::PaymentOverdue->value,
            'approved_budget' => 15000,
            'approved_grant_amount' => 15000,
            'approved_declared_at' => now()->subDays(10),
            'activation_fee_amount' => 150,
            'invoice_status' => Project::INVOICE_OVERDUE,
            'invoice_due_at' => now()->subDay(),
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel('platform');

        $this->assertFalse($project->fresh()->hasPaymentOverdue());
        $this->assertTrue($project->fresh()->implementationModulesAvailable());

        Livewire::test(ListPlatformProjectPayments::class)
            ->assertDontSee('Unlimited Project With Legacy Invoice');
    }
}
