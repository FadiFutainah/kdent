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

            $fcmToken = 'fXuRuNgBT7qvMeCITSQka2:APA91bGk8849MC6UpvBYGfKv6i6ZF6ggs41FHnJfCXQxnqD8tUp_nXWXBcJmwdk3dAaVs83c8elMmkoaoAAzA8plm-8uRea7F6Sf57pOU1H4623N-ZoKJDc';

            $title = 'Test Notification - إشعار تجريبي';

            $body = 'This is a test notification from Karam Dent system. نظام الإشعارات يعمل بنجاح!';

            $message = CloudMessage::new()
                ->toToken($fcmToken)
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