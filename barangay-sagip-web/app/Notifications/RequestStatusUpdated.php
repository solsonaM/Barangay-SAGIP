<?php

namespace App\Notifications;

use App\Models\EmergencyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Feature 10: Alerts and Notifications.
 *
 * Sent to the resident whenever their request's status changes (submitted,
 * validated, assigned, en route, resolved, etc.). Uses Laravel's built-in
 * database notifications channel so it shows up in the in-app notification
 * list (Feature 10) without requiring SMS/mail setup to demo; add 'mail' or
 * a custom 'sms' channel to `via()` once those are configured.
 */
class RequestStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public EmergencyRequest $emergencyRequest, public string $newStatus)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'emergency_request_id' => $this->emergencyRequest->id,
            'status' => $this->newStatus,
            'message' => "Your request #{$this->emergencyRequest->id} is now: " . ucfirst(str_replace('_', ' ', $this->newStatus)),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Barangay SAGIP: Request #{$this->emergencyRequest->id} update")
            ->line("Your request status is now: " . ucfirst(str_replace('_', ' ', $this->newStatus)))
            ->action('View Request', route('requests.show', $this->emergencyRequest));
    }
}
