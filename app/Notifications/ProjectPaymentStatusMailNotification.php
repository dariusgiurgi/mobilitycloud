<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectPaymentStatusMailNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
        public string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->project;
        $fee = '€ '.number_format((float) $project->activation_fee_amount, 2);
        $due = $project->invoice_due_at?->format('d M Y');

        $message = (new MailMessage)
            ->replyTo(config('mobilitycloud.emails.support', 'contact@mobilitycloud.eu'), 'MobilityCloud Support')
            ->greeting('Hello '.$notifiable->name.',');

        return match ($this->status) {
            Project::INVOICE_SENT => $message
                ->subject('MobilityCloud invoice update · '.$project->name)
                ->line('The fiscal invoice for your approved project has been marked as sent.')
                ->line('Project: '.$project->name)
                ->line('Platform administration fee: '.$fee)
                ->line($due ? 'Payment due date: '.$due : 'Please follow the payment term shown on the fiscal invoice.')
                ->line('Your project remains available while the invoice is handled manually.'),
            Project::INVOICE_PAID => $message
                ->subject('Payment confirmed · '.$project->name)
                ->line('Payment for your MobilityCloud project administration fee has been confirmed.')
                ->line('Project: '.$project->name)
                ->line('Confirmed amount: '.$fee)
                ->line('Implementation modules are available according to your project access.'),
            Project::INVOICE_OVERDUE => $message
                ->subject('Payment overdue · '.$project->name)
                ->line('The payment term for your MobilityCloud project administration fee has passed.')
                ->line('Project: '.$project->name)
                ->line('Outstanding amount: '.$fee)
                ->line($due ? 'Due date: '.$due : 'Please contact support for payment details.')
                ->line('Project access may be limited until payment is confirmed.'),
            default => $message
                ->subject('MobilityCloud project payment update · '.$project->name)
                ->line('There is an update regarding your project payment status.')
                ->line('Project: '.$project->name),
        };
    }
}
