<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ProjectInvitationNotificationService
{
    public function notifyExistingAccount(WorkspaceInvitation $invitation, ?User $user = null): bool
    {
        $invitation->loadMissing(['workspace.users', 'project', 'inviter']);

        if (! $this->canNotify($invitation)) {
            return false;
        }

        $user ??= User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower((string) $invitation->email)])
            ->first();

        if (! $user || strcasecmp((string) $user->email, (string) $invitation->email) !== 0) {
            return false;
        }

        if ($this->alreadyNotified($user, $invitation)) {
            return false;
        }

        $projectRole = $this->projectRole($invitation);
        $project = $invitation->project;
        $inviter = $invitation->inviter?->name ?: 'A project owner';

        Notification::make()
            ->title('Project invitation received')
            ->body($inviter.' invited you to '.$project?->name.' as '.Project::projectRoleLabel($projectRole).'. Accept it to add the project to your Projects section.')
            ->info()
            ->viewData([
                'kind' => 'project_invitation',
                'invitation_id' => $invitation->id,
                'project_id' => $invitation->project_id,
                'workspace_id' => $invitation->workspace_id,
            ])
            ->actions([
                Action::make('acceptProjectInvitation')
                    ->label('Accept invitation')
                    ->button()
                    ->markAsRead()
                    ->url(route('workspace-invitations.accept', $invitation->token)),
            ])
            ->sendToDatabase($user, isEventDispatched: true);

        return true;
    }

    public function syncPendingFor(User $user): int
    {
        $count = 0;

        WorkspaceInvitation::query()
            ->with(['workspace.users', 'project', 'inviter'])
            ->whereRaw('LOWER(email) = ?', [strtolower((string) $user->email)])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->whereNotNull('project_id')
            ->where('role', 'like', 'project_%')
            ->latest()
            ->get()
            ->each(function (WorkspaceInvitation $invitation) use ($user, &$count): void {
                if ($this->notifyExistingAccount($invitation, $user)) {
                    $count++;
                }
            });

        return $count;
    }

    public function markAccepted(WorkspaceInvitation $invitation, User $user): void
    {
        $user->notifications()
            ->get()
            ->filter(fn ($notification): bool => (int) data_get($notification->data, 'viewData.invitation_id') === (int) $invitation->id)
            ->each->markAsRead();
    }

    private function canNotify(WorkspaceInvitation $invitation): bool
    {
        return $invitation->isPending()
            && $invitation->project_id !== null
            && $invitation->project !== null
            && str_starts_with((string) $invitation->role, 'project_');
    }

    private function projectRole(WorkspaceInvitation $invitation): string
    {
        $role = str((string) $invitation->role)->after('project_')->toString();

        return array_key_exists($role, Project::projectRoleOptions())
            ? $role
            : Project::PROJECT_ROLE_EDITOR;
    }

    private function alreadyNotified(User $user, WorkspaceInvitation $invitation): bool
    {
        return $user->notifications()
            ->get()
            ->contains(fn ($notification): bool => (int) data_get($notification->data, 'viewData.invitation_id') === (int) $invitation->id);
    }
}
