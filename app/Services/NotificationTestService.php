<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class NotificationTestService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(
                config('services.firebase.credentials')
            );

        $this->messaging = $factory->createMessaging();
    }

    public function sendTestNotificationToFcm()
    {
        try {

            $fcmToken = 'ct1QzVKmRfekgki8qxjdjJ:APA91bEOhMMl4kUZGf_rxZ9oU2UUoQvnhCYhEH545hYCKmQ_GFJG6yA6W3C1G2uuklxUhVH_q9cZ1OQxjg1x9lMOhk6j6FipChjvuW8BBCUSx-cAY-v9lrY';

            $title = 'Test Notification - إشعار تجريبي';

            $body = 'This is a test notification from Karam Dent system. نظام الإشعارات يعمل بنجاح!';

            $message = CloudMessage::new()
                ->withToken($fcmToken)
                ->withNotification(
                    Notification::create(
                        $title,
                        $body
                    )
                )
                ->withData([
                    'type' => 'test',
                    'timestamp' => now()->toDateTimeString(),
                ]);

            $response = $this->messaging->send($message);

            return [
                'success' => true,
                'message' => 'Test notification sent successfully',
                'firebase_response' => $response,
            ];

        } catch (\Throwable $e) {

            Log::error('Firebase notification exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage(),
            ];
        }
    }
}