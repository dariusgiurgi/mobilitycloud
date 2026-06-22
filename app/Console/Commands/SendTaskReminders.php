<?php

namespace App\Console\Commands;

use App\Services\TaskReminderService;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:send-reminders';

    protected $description = 'Send in-app notifications for approaching and overdue project tasks';

    public function handle(TaskReminderService $reminders): int
    {
        $sent = $reminders->dispatch();

        $this->info($sent.' task '.str('notification')->plural($sent).' queued.');

        return self::SUCCESS;
    }
}
