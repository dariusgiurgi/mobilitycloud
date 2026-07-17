<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PlatformProjectPayments\PlatformProjectPaymentResource;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class PlatformProjectPaymentsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Payment work queue';

    protected ?string $description = 'Prioritised manual invoicing: send fiscal invoices, chase overdue payments and unlock projects after payment.';

    protected function getStats(): array
    {
        $toInvoice = PlatformProjectPaymentResource::applyToInvoiceScope($this->paymentQuery());
        $overdue = PlatformProjectPaymentResource::applyOverdueScope($this->paymentQuery());
        $sent = $this->paymentQuery()->where('invoice_status', Project::INVOICE_SENT);
        $missingBilling = PlatformProjectPaymentResource::applyMissingBillingScope($this->paymentQuery());
        $paidThisMonth = $this->paymentQuery()
            ->where('invoice_status', Project::INVOICE_PAID)
            ->whereNotNull('payment_confirmed_at')
            ->where('payment_confirmed_at', '>=', now()->startOfMonth());

        return [
            Stat::make('To invoice', number_format((clone $toInvoice)->count()))
                ->description($this->formatAmount((clone $toInvoice)->sum('activation_fee_amount')).' ready to bill')
                ->color('warning'),
            Stat::make('Overdue', number_format((clone $overdue)->count()))
                ->description($this->formatAmount((clone $overdue)->sum('activation_fee_amount')).' needs follow-up')
                ->color('danger'),
            Stat::make('Invoice sent', number_format((clone $sent)->count()))
                ->description($this->formatAmount((clone $sent)->sum('activation_fee_amount')).' awaiting payment')
                ->color('info'),
            Stat::make('Missing billing', number_format((clone $missingBilling)->count()))
                ->description('Cannot invoice until billing details are complete')
                ->color('danger'),
            Stat::make('Paid this month', number_format((clone $paidThisMonth)->count()))
                ->description($this->formatAmount((clone $paidThisMonth)->sum('activation_fee_amount')).' confirmed')
                ->color('success'),
        ];
    }

    private function paymentQuery(): Builder
    {
        return PlatformProjectPaymentResource::paymentQueueQuery();
    }

    private function formatAmount(float|int|string|null $amount): string
    {
        return '€ '.number_format((float) $amount, 2);
    }
}
