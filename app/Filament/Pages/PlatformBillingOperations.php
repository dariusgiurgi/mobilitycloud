<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PlatformProjectPayments\PlatformProjectPaymentResource;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Models\Project;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PlatformBillingOperations extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & access';

    protected static ?string $navigationLabel = 'Billing operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Billing operations';

    protected static ?string $slug = 'billing-operations';

    protected string $view = 'filament.pages.platform-billing-operations';

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdmin() ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'A practical billing workbench for projects that need external fiscal invoicing, payment follow-up or billing-data cleanup.';
    }

    public function cards(): array
    {
        $toInvoice = PlatformProjectPaymentResource::applyToInvoiceScope($this->paymentQuery());
        $overdue = PlatformProjectPaymentResource::applyOverdueScope($this->paymentQuery());
        $sent = $this->sentAwaitingPaymentQuery();
        $missingBilling = PlatformProjectPaymentResource::applyMissingBillingScope($this->paymentQuery());
        $paidThisMonth = $this->paymentQuery()
            ->where('invoice_status', Project::INVOICE_PAID)
            ->whereNotNull('payment_confirmed_at')
            ->where('payment_confirmed_at', '>=', now()->startOfMonth());

        return [
            [
                'label' => 'Ready to invoice',
                'count' => (clone $toInvoice)->count(),
                'amount' => $this->formatEuro((clone $toInvoice)->sum('activation_fee_amount')),
                'detail' => 'Billing details complete, fiscal invoice not sent yet.',
                'level' => 'warning',
                'url' => $this->paymentQueueUrl('to_invoice'),
            ],
            [
                'label' => 'Overdue payments',
                'count' => (clone $overdue)->count(),
                'amount' => $this->formatEuro((clone $overdue)->sum('activation_fee_amount')),
                'detail' => 'Payment term passed or manually marked overdue.',
                'level' => 'danger',
                'url' => $this->paymentQueueUrl('overdue'),
            ],
            [
                'label' => 'Invoice sent',
                'count' => (clone $sent)->count(),
                'amount' => $this->formatEuro((clone $sent)->sum('activation_fee_amount')),
                'detail' => 'Waiting for external payment confirmation.',
                'level' => 'info',
                'url' => $this->paymentQueueUrl('sent'),
            ],
            [
                'label' => 'Missing billing',
                'count' => (clone $missingBilling)->count(),
                'amount' => '—',
                'detail' => 'Cannot invoice before the account completes billing details.',
                'level' => 'danger',
                'url' => $this->paymentQueueUrl('missing_billing'),
            ],
            [
                'label' => 'Paid this month',
                'count' => (clone $paidThisMonth)->count(),
                'amount' => $this->formatEuro((clone $paidThisMonth)->sum('activation_fee_amount')),
                'detail' => 'Externally confirmed administration fees.',
                'level' => 'success',
                'url' => $this->paymentQueueUrl('paid'),
            ],
        ];
    }

    public function todayQueue(): Collection
    {
        return PlatformProjectPaymentResource::applyToInvoiceScope($this->paymentQuery())
            ->orderBy('invoice_due_at')
            ->orderBy('created_at')
            ->limit(8)
            ->get();
    }

    public function overdueQueue(): Collection
    {
        return PlatformProjectPaymentResource::applyOverdueScope($this->paymentQuery())
            ->orderBy('invoice_due_at')
            ->orderBy('updated_at')
            ->limit(8)
            ->get();
    }

    public function sentQueue(): Collection
    {
        return $this->sentAwaitingPaymentQuery()
            ->orderBy('invoice_due_at')
            ->orderByDesc('invoice_sent_at')
            ->limit(8)
            ->get();
    }

    public function missingBillingQueue(): Collection
    {
        return PlatformProjectPaymentResource::applyMissingBillingScope($this->paymentQuery())
            ->orderByDesc('approved_declared_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
    }

    public function paymentQueueUrl(string $tab = 'to_invoice'): string
    {
        return PlatformProjectPaymentResource::getUrl(panel: 'platform').'?tab='.$tab;
    }

    public function accountUrl(Project $project): ?string
    {
        return $project->ownerAccount
            ? PlatformUserResource::getUrl('edit', ['record' => $project->ownerAccount], panel: 'platform')
            : null;
    }

    public function formatEuro(float|int|string|null $amount): string
    {
        return '€ '.number_format((float) $amount, 2);
    }

    private function paymentQuery(): Builder
    {
        return PlatformProjectPaymentResource::paymentQueueQuery();
    }

    private function sentAwaitingPaymentQuery(): Builder
    {
        return $this->paymentQuery()
            ->where('invoice_status', Project::INVOICE_SENT)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('invoice_due_at')
                    ->orWhere('invoice_due_at', '>=', now());
            });
    }
}
