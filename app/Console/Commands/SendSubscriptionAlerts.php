<?php

namespace App\Console\Commands;

use App\Services\SubscriptionAlertService;
use Illuminate\Console\Command;

class SendSubscriptionAlerts extends Command
{
    protected $signature = 'subscriptions:send-alerts';

    protected $description = 'Send internal platform notifications for trials, expirations, manual access and demo reset issues';

    public function handle(SubscriptionAlertService $alerts): int
    {
        $sent = $alerts->dispatch();

        $this->info($sent.' subscription '.str('alert')->plural($sent).' queued.');

        return self::SUCCESS;
    }
}
