<?php

namespace App\Console\Commands;

use App\Services\SubscriptionAlertService;
use Illuminate\Console\Command;

class SendSubscriptionAlerts extends Command
{
    protected $signature = 'subscriptions:send-alerts';

    protected $description = 'Send internal platform notifications for account access issues';

    public function handle(SubscriptionAlertService $alerts): int
    {
        $sent = $alerts->dispatch();

        $this->info($sent.' account access '.str('alert')->plural($sent).' queued.');

        return self::SUCCESS;
    }
}
