<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\FcmTokenService;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function __construct(
        private FcmTokenService $fcmTokenService
    ) {}

    /**
     * حفظ FCM Token للمستخدم الحالي
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $this->fcmTokenService->saveToken(
            $user,
            $validated['fcm_token']
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ FCM Token بنجاح',
        ]);
    }

    /**
     * حذف FCM Token
     */
    public function destroy(Request $request)
    {
        $this->fcmTokenService->removeToken(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حذف FCM Token بنجاح',
        ]);
    }
}