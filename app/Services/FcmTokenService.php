<?php

namespace App\Services;

use App\Models\User;

class FcmTokenService
{
    public function saveToken(User $user, string $token): User
    {
        $user->update([
            'fcm_token' => $token,
        ]);

        return $user->fresh();
    }

    public function removeToken(User $user): void
    {
        $user->update([
            'fcm_token' => null,
        ]);
    }
}