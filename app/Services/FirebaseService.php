<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    private function getAccessToken()
    {
        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            json_decode(file_get_contents(storage_path('app/firebase.json')), true)
        );

        return $credentials->fetchAuthToken()['access_token'];
    }

    public function sendToToken($token, $title, $body, $data = [])
    {
        $projectId = env('FIREBASE_PROJECT_ID');

        Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json',
        ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
            ]
        ]);
    }
}