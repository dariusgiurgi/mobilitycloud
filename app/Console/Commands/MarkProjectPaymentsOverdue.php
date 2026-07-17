<?php

namespace App\Console\Commands;

use App\Services\ProjectPaymentOverdueService;
use Illuminate\Console\Command;

class MarkProjectPaymentsOverdue extends Command
{
    protected $signature = 'project-payments:mark-overdue';

    protected $description = 'Automatically mark overdue project payments and notify account owners';

    public function handle(ProjectPaymentOverdueService $overdue): int
    {
        $count = $overdue->dispatch();

        $this->info($count.' project payment '.str('record')->plural($count).' marked overdue.');

        return self::SUCCESS;
    }
}
