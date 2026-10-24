<?php

namespace Tests\Feature;

use App\Filament\Pages\PlatformBillingOperations;
use App\Filament\Resources\PlatformProjectPayments\Pages\ListPlatformProjectPayments;
use App\Filament\Resources\PlatformProjectPayments\PlatformProjectPaymentResource;
use App\Filament\Widgets\PlatformProjectPaymentsOverview;
use App\Models\PlatformAuditLog;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectPaymentCancellationService;
use App\Services\ProjectPaymentOverdueService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProjectPaymentCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $status = Project::INVOICE_PENDING): Project
    {
        return Project::create([
            'owner_id' => User::factory()->create([
                'billing_name' => 'Test association', 'billing_country' => 'Romania', 'billing_address' => 'Test address',
            ])->id,
            'name' => 'Cancellation test project',
            'status' => $status === Project::INVOICE_OVERDUE ? 'payment_overdue' : 'approved',
            'approved_grant_amount' => 20000,
            'approved_declared_at' => now()->subMonth(),
            'activation_fee_amount' => 100,
            'invoice_status' => $status,
            'invoice_number' => $status === Project::INVOICE_PENDING ? null : 'MC-123',
            'invoice_sent_at' => $status === Project::INVOICE_PENDING ? null : now()->subWeek(),
            'invoice_due_at' => now()->subDay(),
            'payment_confirmed_at' => $status === Project::INVOICE_PAID ? now() : null,
        ]);
    }

    public static function unpaidStatuses(): array
    {
        return [[Project::INVOICE_PENDING], [Project::INVOICE_SENT], [Project::INVOICE_OVERDUE]];
    }

    #[DataProvider('unpaidStatuses')]
    public function test_admin_can_cancel_unpaid_orders_and_invoices_without_losing_history(string $status): void
    {
        $this->actingAs($admin = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]));
        Filament::setCurrentPanel('platform');
        $project = $this->project($status);
        $original = $project->getAttributes();

        Livewire::test(ListPlatformProjectPayments::class)->set('activeTab', 'all')
            ->assertTableActionVisible('cancelInvoice', $project)
            ->callTableAction('cancelInvoice', $project, data: ['reason' => 'Customer changed their mind'])
            ->assertHasNoTableActionErrors();

        $project->refresh();
        $this->assertSame(Project::INVOICE_CANCELLED, $project->invoice_status);
        $this->assertSame('approved', $project->status);
        $this->assertSame('100.00', $project->activation_fee_amount);
        $this->assertSame($original['invoice_number'], $project->invoice_number);
        $this->assertNotNull($project->invoice_cancelled_at);
        $this->assertEquals($admin->id, $project->invoice_cancelled_by);
        $this->assertFalse($project->hasPaymentOverdue());
        $this->assertTrue($project->operationalModulesAvailable());
        $this->assertSame(0, app(ProjectPaymentOverdueService::class)->dispatch());
        $this->assertSame(0, $project->ownerAccount->notifications()->count());
        $audit = PlatformAuditLog::where('action', 'project.invoice_cancelled')->sole();
        $this->assertSame($status, $audit->metadata['previous_invoice_status']);
        $this->assertSame('Customer changed their mind', $audit->metadata['reason']);

        Livewire::test(ListPlatformProjectPayments::class)->set('activeTab', 'cancelled')
            ->assertCanSeeTableRecords([$project])->assertSee('Customer changed their mind')
            ->assertTableActionHidden('cancelInvoice', $project)
            ->assertTableActionHidden('markSent', $project)
            ->assertTableActionHidden('markPaid', $project)
            ->assertTableActionHidden('editInvoice', $project);
    }

    public function test_cancelled_amounts_and_missing_billing_are_excluded_from_financial_cards(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]));
        Filament::setCurrentPanel('platform');
        foreach (self::unpaidStatuses() as [$status]) {
            $project = $this->project($status);
            app(ProjectPaymentCancellationService::class)->cancel($project, 'No longer needed');
            $project->ownerAccount->update(['billing_name' => null]);
        }
        $active = $this->project();
        $active->update(['invoice_due_at' => null]);
        $cards = collect(app(PlatformBillingOperations::class)->cards())->keyBy('label');
        $this->assertSame('€ 100.00', $cards['Ready to invoice']['amount']);
        foreach (['Overdue payments', 'Invoice sent', 'Missing billing', 'Paid this month'] as $label) {
            $this->assertSame(0, $cards[$label]['count']);
        }
        Livewire::test(PlatformProjectPaymentsOverview::class)
            ->assertSee('€ 100.00 ready to bill')->assertSee('€ 0.00 needs follow-up')->assertSee('€ 0.00 awaiting payment');
    }

    public function test_reason_is_required_and_paid_invoices_cannot_be_cancelled_or_downgraded(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]));
        Filament::setCurrentPanel('platform');
        $project = $this->project();
        Livewire::test(ListPlatformProjectPayments::class)->set('activeTab', 'all')
            ->callTableAction('cancelInvoice', $project, data: ['reason' => ''])
            ->assertHasTableActionErrors(['reason' => 'required']);
        $paid = $this->project(Project::INVOICE_PAID);
        Livewire::test(ListPlatformProjectPayments::class)->set('activeTab', 'paid')
            ->assertTableActionHidden('cancelInvoice', $paid);
        try {
            (new \ReflectionMethod(PlatformProjectPaymentResource::class, 'updateInvoice'))->invoke(null, $paid, [
                'approved_grant_amount' => 20000, 'invoice_status' => Project::INVOICE_PENDING,
            ], 'project.invoice_updated');
            $this->fail('A paid invoice must not be downgraded to bypass cancellation validation.');
        } catch (ValidationException) {
            $this->assertSame(Project::INVOICE_PAID, $paid->fresh()->invoice_status);
        }
        $this->expectException(ValidationException::class);
        app(ProjectPaymentCancellationService::class)->cancel($paid, 'Attempt to remove a payment');
    }

    public function test_regular_account_cannot_cancel_even_when_calling_the_service_directly(): void
    {
        $this->actingAs(User::factory()->create());
        $project = $this->project();
        try {
            app(ProjectPaymentCancellationService::class)->cancel($project, 'Unauthorized');
            $this->fail('Expected forbidden.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame(Project::INVOICE_PENDING, $project->fresh()->invoice_status);
        }
    }

    public function test_stale_invoice_edits_overdue_jobs_and_approval_cannot_reactivate_a_cancellation(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]));
        $stale = $this->project(Project::INVOICE_SENT);
        app(ProjectPaymentCancellationService::class)->cancel($stale, 'Changed mind');
        $this->assertFalse((new \ReflectionMethod(ProjectPaymentOverdueService::class, 'markOverdue'))
            ->invoke(app(ProjectPaymentOverdueService::class), $stale));
        try {
            (new \ReflectionMethod(PlatformProjectPaymentResource::class, 'updateInvoice'))->invoke(null, $stale, [
                'approved_grant_amount' => 20000, 'invoice_status' => Project::INVOICE_PAID,
            ], 'project.invoice_paid');
            $this->fail('Stale invoice update must be rejected.');
        } catch (ValidationException) {
            $this->assertSame(Project::INVOICE_CANCELLED, $stale->fresh()->invoice_status);
        }
        $this->expectException(ValidationException::class);
        $stale->declareApprovedGrant(20000, auth()->user());
    }

    public function test_stale_cancellation_cannot_remove_a_payment_confirmed_in_the_meantime(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]));
        $stale = $this->project(Project::INVOICE_SENT);
        $stale->fresh()->update(['invoice_status' => Project::INVOICE_PAID, 'payment_confirmed_at' => now()]);
        $this->expectException(ValidationException::class);
        app(ProjectPaymentCancellationService::class)->cancel($stale, 'Stale cancellation');
    }
}
