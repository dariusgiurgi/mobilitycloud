<?php

namespace App\Services;

use App\Models\ProjectTask;

class TaskReminderService
{
    public function dispatch(): int
    {
        $sent = 0;
        $notifications = app(TaskNotificationService::class);

        ProjectTask::query()
            ->where('status', 'open')
            ->whereNotNull('assigned_to')
            ->whereNotNull('due_date')
            ->where('due_date', '<=', today()->addDays(3))
            ->with(['assignee', 'project'])
            ->orderBy('id')
            ->chunkById(100, function ($tasks) use ($notifications, &$sent): void {
                foreach ($tasks as $task) {
                    if ($task->due_date->isBefore(today())) {
                        if ($task->overdue_notified_at || ! $notifications->sendOverdue($task)) {
                            continue;
                        }

                        $task->overdue_notified_at = now();
                    } else {
                        if ($task->reminder_sent_at || ! $notifications->sendDueSoon($task)) {
                            continue;
                        }

                        $task->reminder_sent_at = now();
                    }

                    $task->saveQuietly();
                    $sent++;
                }
            });

        return $sent;
    }
}
