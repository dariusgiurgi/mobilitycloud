<?php

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
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

        return (new MailMessage)
            ->replyTo(config('mobilitycloud.emails.support', 'contact@mobilitycloud.eu'), 'MobilityCloud Support')
            ->subject('Join '.$workspace->name.' on MobilityCloud')
            ->greeting('You have been invited')
            ->line(($this->invitation->inviter?->name ?? 'A workspace administrator').' invited you to collaborate in '.$workspace->name.'.')
            ->line('Your access level will be: '.ucfirst($this->invitation->role).'.')
            ->action('Accept invitation', route('workspace-invitations.accept', $this->invitation->token))
            ->line('This invitation expires on '.$this->invitation->expires_at->format('d M Y, H:i').'.');
    }
}
