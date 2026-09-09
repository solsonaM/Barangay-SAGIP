<?php

namespace App\Notifications;

use App\Models\ResponseAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(public ResponseAssignment $assignment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $request = $this->assignment->emergencyRequest;

        return [
            'assignment_id' => $this->assignment->id,
            'emergency_request_id' => $request->id,
            'message' => "You have been assigned to request #{$request->id} ({$request->category}, {$request->urgency?->value}).",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->assignment->emergencyRequest;

        return (new MailMessage)
            ->subject("New assignment: Request #{$request->id}")
            ->line("You've been assigned to a {$request->urgency?->value} priority {$request->category} request.")
            ->line($request->description)
            ->action('View Request', route('requests.show', $request));
    }
}
