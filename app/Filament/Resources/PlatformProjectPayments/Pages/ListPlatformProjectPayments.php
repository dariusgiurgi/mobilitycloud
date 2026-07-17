<?php

namespace App\Filament\Resources\PlatformProjectPayments\Pages;

use App\Filament\Resources\PlatformProjectPayments\PlatformProjectPaymentResource;
use App\Filament\Widgets\PlatformProjectPaymentsOverview;
use App\Models\Project;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPlatformProjectPayments extends ListRecords
{
    protected static string $resource = PlatformProjectPaymentResource::class;

    public function getSubheading(): ?string
    {
        return 'Approved-project billing queue: invoice data, payment status and project access unlocks.';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlatformProjectPaymentsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'to_invoice' => Tab::make('To invoice')
                ->badge(fn (): int => PlatformProjectPaymentResource::applyToInvoiceScope(
                    PlatformProjectPaymentResource::paymentQueueQuery()
                )->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => PlatformProjectPaymentResource::applyToInvoiceScope($query)),
            'overdue' => Tab::make('Overdue')
                ->badge(fn (): int => PlatformProjectPaymentResource::applyOverdueScope(
                    PlatformProjectPaymentResource::paymentQueueQuery()
                )->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => PlatformProjectPaymentResource::applyOverdueScope($query)),
            'sent' => Tab::make('Sent invoices')
                ->badge(fn (): int => PlatformProjectPaymentResource::paymentQueueQuery()
                    ->where('invoice_status', Project::INVOICE_SENT)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('invoice_status', Project::INVOICE_SENT)),
            'missing_billing' => Tab::make('Missing billing')
                ->badge(fn (): int => PlatformProjectPaymentResource::applyMissingBillingScope(
                    PlatformProjectPaymentResource::paymentQueueQuery()
                )->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => PlatformProjectPaymentResource::applyMissingBillingScope($query)),
            'paid' => Tab::make('Paid')
                ->badge(fn (): int => PlatformProjectPaymentResource::paymentQueueQuery()
                    ->where('invoice_status', Project::INVOICE_PAID)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('invoice_status', Project::INVOICE_PAID)),
            'all' => Tab::make('All'),
        ];
    }
}
