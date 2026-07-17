<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\ProjectInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public ProjectInvitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->invitation->project;
        $accessLabel = str_starts_with($this->invitation->role, 'project_')
            ? Project::projectRoleLabel(str($this->invitation->role)->after('project_')->toString())
            : ucfirst($this->invitation->role);

        return (new MailMessage)
            ->replyTo(config('mobilitycloud.emails.support', 'contact@mobilitycloud.eu'), 'MobilityCloud Support')
            ->subject('Join '.($project?->name ?? 'a project').' on MobilityCloud')
            ->greeting('You have been invited')
            ->line(($this->invitation->inviter?->name ?? 'A project owner').' invited you to collaborate in '.($project?->name ?? 'a MobilityCloud project').'.')
            ->line('After you accept, this project will appear in your Projects section.')
            ->line('Your access level will be: '.$accessLabel.'.')
            ->action('Accept invitation', route('project-invitations.accept', $this->invitation->token))
            ->line('This invitation expires on '.$this->invitation->expires_at->format('d M Y, H:i').'.');
    }
}
