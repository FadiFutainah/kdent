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

            $fcmToken = 'd4NNVi_eSg2s937sh5rQ9T:APA91bEM4gSFGqKdI6O-l2885skt7mD_Nuy7qcErZRXhLAToWMJu97VfBG-fFkeGengziPZUQ9K4dw8K7jxLyl173d6EdAMQLPrqITfYP9aBrJAmqnEydL4';

            $title = 'Test Notification -  doaa إشعار تجريبي';

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