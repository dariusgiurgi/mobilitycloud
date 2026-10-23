<?php

namespace Tests\Feature;

use App\Filament\Pages\PlatformProjectPricing;
use App\Filament\Resources\PlatformProjectPayments\Pages\ListPlatformProjectPayments;
use App\Filament\Resources\PlatformUsers\Pages\EditPlatformUser;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectPricing;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProjectPricingTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]));
        Filament::setCurrentPanel('platform');
    }

    private function project(User $owner): Project
    {
        return Project::create(['owner_id' => $owner->id, 'name' => 'Pricing test', 'status' => 'submitted']);
    }

    public function test_global_changes_follow_accounts_but_preserve_existing_project_fees(): void
    {
        $this->administrator();
        $owner = User::factory()->create();
        $approved = $this->project($owner);
        $draft = $this->project($owner);
        $approved->declareApprovedGrant(60000);
        $this->assertSame('300.00', $approved->activation_fee_amount);

        Livewire::test(PlatformProjectPricing::class)
            ->assertSee('0.5%')
            ->callAction('globalFee', data: ['percentage' => 1])
            ->assertHasNoActionErrors()
            ->assertSee('1%');

        $draft->declareApprovedGrant(60000);
        $this->assertSame('600.00', $draft->activation_fee_amount);
        $this->assertSame('300.00', $approved->fresh()->activation_fee_amount);
        $this->assertSame(300.0, $approved->fresh()->administrationFeeForApprovedGrant(60000));
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'pricing.global_updated']);
        $this->get('/pricing')->assertOk()->assertSee('charges 1% of the approved grant', false);
        $this->get(route('legal.terms'))->assertOk()->assertSee('calculated as 1%', false);
    }

    public function test_custom_tariffs_can_be_set_in_the_account_interface_and_reset_to_global(): void
    {
        $this->administrator();
        $owner = User::factory()->create();
        $component = Livewire::test(EditPlatformUser::class, ['record' => $owner->id]);
        $component->callAction('projectFee', data: ['mode' => 'percentage', 'value' => 0.75])->assertHasNoActionErrors();
        $this->assertSame('percentage', $owner->fresh()->project_fee_mode);
        app(ProjectPricing::class)->setGlobal(1);
        $project = $this->project($owner->fresh());
        $project->declareApprovedGrant(60000);
        $this->assertSame('450.00', $project->activation_fee_amount);

        $component->callAction('projectFee', data: ['mode' => 'fixed', 'value' => 275.50])->assertHasNoActionErrors();
        foreach ([20000, 100000] as $grant) {
            $fixed = $this->project($owner->fresh());
            $fixed->declareApprovedGrant($grant);
            $this->assertSame('275.50', $fixed->activation_fee_amount);
        }

        $component->callAction('projectFee', data: ['mode' => 'global'])->assertHasNoActionErrors();
        $owner->refresh();
        $this->assertNull($owner->project_fee_value);
        $this->assertSame(600.0, $this->project($owner)->administrationFeeForApprovedGrant(60000));
        $this->assertSame('450.00', $project->fresh()->activation_fee_amount);
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'pricing.account_updated', 'subject_id' => $owner->id]);
    }

    public function test_invoice_payment_does_not_reprice_new_or_legacy_fees(): void
    {
        $this->administrator();
        $owner = User::factory()->create();
        $owner->update(['billing_name' => 'Test association', 'billing_vat' => 'RO1234', 'billing_country' => 'Romania', 'billing_address' => 'Test street']);
        $new = $this->project($owner);
        $new->declareApprovedGrant(20000);
        $legacy = Project::create([
            'owner_id' => $owner->id, 'name' => 'Legacy price', 'status' => 'approved',
            'approved_grant_amount' => 20000, 'approved_declared_at' => now()->subMonth(),
            'activation_fee_amount' => 200, 'invoice_status' => Project::INVOICE_PENDING,
        ]);
        app(ProjectPricing::class)->setGlobal(1);
        app(ProjectPricing::class)->setAccount($owner, ['mode' => 'fixed', 'value' => 500]);

        foreach ([$new, $legacy] as $project) {
            $fee = $project->activation_fee_amount;
            Livewire::test(ListPlatformProjectPayments::class)->callTableAction('markSent', $project, ['invoice_number' => 'TEST-1', 'invoice_due_at' => now()->addMonth()->format('Y-m-d H:i:s')])->assertHasNoTableActionErrors();
            $this->assertSame($fee, $project->fresh()->activation_fee_amount);
            Livewire::test(ListPlatformProjectPayments::class)->set('activeTab', 'sent')->callTableAction('markPaid', $project)->assertHasNoTableActionErrors();
            $this->assertSame(Project::INVOICE_PAID, $project->fresh()->invoice_status);
            $this->assertSame($fee, $project->fresh()->activation_fee_amount);
        }
    }

    public function test_pending_grant_corrections_use_the_agreed_rate_and_invoiced_grants_are_locked(): void
    {
        $this->administrator();
        $owner = User::factory()->create();
        $project = $this->project($owner);
        $project->declareApprovedGrant(20000);
        app(ProjectPricing::class)->setGlobal(1);
        Livewire::test(ListPlatformProjectPayments::class)
            ->callTableAction('editInvoice', $project, ['approved_grant_amount' => 30000, 'invoice_status' => Project::INVOICE_PENDING])
            ->assertHasNoTableActionErrors();
        $this->assertSame('150.00', $project->fresh()->activation_fee_amount);
        $project->update(['invoice_status' => Project::INVOICE_SENT, 'invoice_sent_at' => now()]);
        Livewire::test(ListPlatformProjectPayments::class)
            ->set('activeTab', 'sent')
            ->callTableAction('editInvoice', $project, ['approved_grant_amount' => 40000, 'invoice_status' => Project::INVOICE_SENT])
            ->assertHasTableActionErrors();
        $this->assertSame('30000.00', $project->fresh()->approved_grant_amount);
        $this->assertSame('150.00', $project->fresh()->activation_fee_amount);
    }

    public function test_platform_admin_cannot_override_another_admin_account_tariff(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]));
        $owner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $this->expectException(HttpException::class);
        app(ProjectPricing::class)->setAccount($owner, ['mode' => 'fixed', 'value' => 1]);
    }

    public function test_custom_tariff_uses_the_project_owner_and_unlimited_accounts_remain_exempt(): void
    {
        $this->administrator();
        $owner = User::factory()->create();
        app(ProjectPricing::class)->setAccount($owner, ['mode' => 'percentage', 'value' => 0.75]);
        $collaborator = User::factory()->create();
        $this->actingAs($collaborator);
        $project = $this->project($owner);
        $project->declareApprovedGrant(12345.67, $collaborator);
        $this->assertSame('92.59', $project->activation_fee_amount);
        $unlimited = User::factory()->create(['plan' => 'unlimited', 'feature_flags' => ['unlimited']]);
        $free = $this->project($unlimited);
        $free->declareApprovedGrant(60000);
        $this->assertSame('0.00', $free->activation_fee_amount);
        $this->assertSame(Project::INVOICE_NOT_REQUIRED, $free->invoice_status);
    }

    public function test_invalid_rates_are_rejected_without_updating_settings(): void
    {
        $this->administrator();
        foreach ([-1, 0, 101, 'abc', 0.555] as $value) {
            Livewire::test(PlatformProjectPricing::class)->callAction('globalFee', data: ['percentage' => $value])->assertHasActionErrors();
        }
        $this->assertSame(0.5, app(ProjectPricing::class)->globalPercentage());
        $owner = User::factory()->create();
        foreach ([['mode' => 'fixed'], ['mode' => 'percentage', 'value' => 101], ['mode' => 'fixed', 'value' => -1], ['mode' => 'invalid', 'value' => 1]] as $data) {
            try {
                app(ProjectPricing::class)->setAccount($owner, $data);
                $this->fail('Invalid pricing was accepted');
            } catch (ValidationException) {
                $this->assertSame('global', $owner->fresh()->project_fee_mode);
            }
        }
    }

    public function test_regular_users_cannot_change_pricing_or_open_the_admin_page(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        Filament::setCurrentPanel('platform');
        Livewire::test(PlatformProjectPricing::class)->assertForbidden();
        foreach ([fn () => app(ProjectPricing::class)->setGlobal(1), fn () => app(ProjectPricing::class)->setAccount($owner, ['mode' => 'fixed', 'value' => 1])] as $mutation) {
            try {
                $mutation();
                $this->fail('Unauthorized pricing change');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
        $this->assertFalse($owner->isFillable('project_fee_mode'));
        $this->assertFalse($owner->isFillable('project_fee_value'));
    }
}
