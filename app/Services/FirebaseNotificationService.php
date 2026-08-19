<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
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

    /**
     * إرسال إشعار لمستخدم واحد بواسطة FCM Token
     */
    public function sendToToken(
        string $fcmToken,
        string $title,
        string $body,
        array $data = []
    ): bool {
        try {

            $message = CloudMessage::new()
                ->toToken($fcmToken)
                ->withNotification(
                    Notification::create(
                        $title,
                        $body
                    )
                )
                ->withData(
                    array_merge(
                        [
                            'type' => 'general',
                        ],
                        $this->normalizeData($data)
                    )
                );

            $this->messaging->send($message);

            return true;

        } catch (\Throwable $e) {

            Log::error('Firebase notification failed', [
                'error' => $e->getMessage(),
                'token' => substr($fcmToken, 0, 15) . '...',
            ]);

            return false;
        }
    }

    /**
     * تحويل بيانات الـ data إلى strings
     * لأن FCM Data Messages تحتاج قيم نصية.
     */
    private function normalizeData(array $data): array
    {
        return collect($data)
            ->map(function ($value) {
                if (is_array($value) || is_object($value)) {
                    return json_encode($value);
                }

                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }

                return (string) $value;
            })
            ->toArray();
    }
}