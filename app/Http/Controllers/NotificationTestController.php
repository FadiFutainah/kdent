<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationTestService;

class NotificationTestController extends Controller
{
    protected $notificationTestService;

    public function __construct(NotificationTestService $notificationTestService)
    {
        $this->notificationTestService = $notificationTestService;
    }

    /**
     * Send simple test notification to current authenticated user
     * Fixed title and body - anyone can call this
     * 
     * @api POST /api/test-notification
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
public function testMe()
{
    return response()->json(
        app(NotificationTestService::class)->sendTestNotificationToFcm()
    );
}
   
}
