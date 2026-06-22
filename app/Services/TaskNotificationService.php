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
        if (! $this->canNotifyAssignee($task)) {
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
        if (! $this->canNotifyAssignee($task)) {
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
        if (! $this->canNotifyAssignee($task)) {
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

    private function canNotifyAssignee(ProjectTask $task): bool
    {
        return $task->assignee !== null
            && $task->project?->workspace?->users()->whereKey($task->assignee->id)->exists();
    }

    private function viewAction(ProjectTask $task): Action
    {
        return Action::make('viewProject')
            ->label('Open project')
            ->button()
            ->markAsRead()
            ->url(ProjectResource::getUrl(
                'overview',
                ['record' => $task->project],
                panel: 'admin',
                tenant: $task->project->workspace,
            ));
    }

    private function dueSuffix(ProjectTask $task): string
    {
        return $task->due_date ? ' · due '.$task->due_date->format('d M Y') : '';
    }
}
