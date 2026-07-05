<?php

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public WorkspaceInvitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->invitation->workspace;
        $project = $this->invitation->project;
        $accessLabel = str_starts_with($this->invitation->role, 'project_')
            ? Project::projectRoleLabel(str($this->invitation->role)->after('project_')->toString())
            : ucfirst($this->invitation->role);

        return (new MailMessage)
            ->replyTo(config('mobilitycloud.emails.support', 'contact@mobilitycloud.eu'), 'MobilityCloud Support')
            ->subject($project ? 'Join '.$project->name.' on MobilityCloud' : 'Join '.$workspace->name.' on MobilityCloud')
            ->greeting('You have been invited')
            ->line(($this->invitation->inviter?->name ?? 'A workspace administrator').' invited you to collaborate in '.($project?->name ?? $workspace->name).'.')
            ->line($project ? 'You will only see this project inside '.$workspace->name.'.' : 'You will join the workspace '.$workspace->name.'.')
            ->line('Your access level will be: '.$accessLabel.'.')
            ->action('Accept invitation', route('workspace-invitations.accept', $this->invitation->token))
            ->line('This invitation expires on '.$this->invitation->expires_at->format('d M Y, H:i').'.');
    }
}
