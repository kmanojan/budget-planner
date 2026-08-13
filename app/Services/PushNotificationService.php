<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected ?Messaging $messaging = null;

    public function __construct()
    {
        try {
            $this->messaging = app('firebase.messaging');
        } catch (\Exception $e) {
            Log::warning('Firebase messaging not configured properly: ' . $e->getMessage());
        }
    }

    /**
     * Send a notification to a specific user.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$this->messaging || !$user->device_token) {
            return false;
        }

        try {
            $message = CloudMessage::withTarget('token', $user->device_token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send push notification to user {$user->id}: " . $e->getMessage());
            return false;
        }
    }
}
