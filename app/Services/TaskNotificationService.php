<?php

namespace App\Services;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ProjectTask;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class TaskNotificationService
{
    public function sendAssignment(ProjectTask $task): bool
    {
        $task->loadMissing(['assignee', 'project.workspace']);
        if (! $this->canNotifyAssignee($task, 'task_assigned')) {
            return false;
        }

        Notification::make()
            ->title('New task assigned')
            ->body($task->project->name.' · '.$task->title.$this->dueSuffix($task))
            ->info()
            ->actions([$this->viewAction($task)])
            ->sendToDatabase($task->assignee, isEventDispatched: true);

        return true;
    }

    public function sendDueSoon(ProjectTask $task): bool
    {
        $task->loadMissing(['assignee', 'project.workspace']);
        if (! $this->canNotifyAssignee($task, 'task_due_soon')) {
            return false;
        }

        Notification::make()
            ->title('Task deadline approaching')
            ->body($task->project->name.' · '.$task->title.$this->dueSuffix($task))
            ->warning()
            ->actions([$this->viewAction($task)])
            ->sendToDatabase($task->assignee, isEventDispatched: true);

        return true;
    }

    public function sendOverdue(ProjectTask $task): bool
    {
        $task->loadMissing(['assignee', 'project.workspace']);
        if (! $this->canNotifyAssignee($task, 'task_overdue')) {
            return false;
        }

        Notification::make()
            ->title('Task is overdue')
            ->body($task->project->name.' · '.$task->title.$this->dueSuffix($task))
            ->danger()
            ->actions([$this->viewAction($task)])
            ->sendToDatabase($task->assignee, isEventDispatched: true);

        return true;
    }

    private function canNotifyAssignee(ProjectTask $task, string $preference): bool
    {
        return $task->assignee !== null
            && $task->project?->canBeAccessedBy($task->assignee) === true
            && $task->assignee->wantsNotification($preference);
    }

    private function viewAction(ProjectTask $task): Action
    {
        return Action::make('viewProject')
            ->label('Open project')
            ->button()
            ->markAsRead()
            ->url(ProjectResource::projectUrl($task->project, user: $task->assignee));
    }

    private function dueSuffix(ProjectTask $task): string
    {
        return $task->due_date ? ' · due '.$task->due_date->format('d M Y') : '';
    }
}
