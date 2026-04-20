<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function __construct(private FirebaseService $firebase) {}

    public function send($user, $title, $body, $type, $data = [])
    {
        // 1. تخزين في DB
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);

        // 2. إرسال Firebase
        if ($user->fcm_token) {
            $this->firebase->sendToToken(
                $user->fcm_token,
                $title,
                $body,
                array_merge($data, [
                    'notification_id' => $notification->id
                ])
            );
        }

        return $notification;
    }
}