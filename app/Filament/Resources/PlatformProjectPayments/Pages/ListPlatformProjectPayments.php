<?php

namespace App\Filament\Resources\PlatformProjectPayments\Pages;

use App\Filament\Resources\PlatformProjectPayments\PlatformProjectPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListPlatformProjectPayments extends ListRecords
{
    protected static string $resource = PlatformProjectPaymentResource::class;

    public function getSubheading(): ?string
    {
        return 'Approved-project billing queue: invoice data, payment status and project access unlocks.';
    }
}
