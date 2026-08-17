<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController
{
    // POST /api/fcm/token
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json(['status' => 'success']);
    }

    // DELETE /api/fcm/token
    public function deleteFcmToken(Request $request)
    {
        $request->user()->update(['fcm_token' => null]);

        return response()->json(['status' => 'success']);
    }

    // GET /api/notifications
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    // GET /api/notifications/unread
    public function unread(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->latest()
            ->get();

        return response()->json([
            'count' => $notifications->count(),
            'data'  => $notifications,
        ]);
    }

    // POST /api/notifications/{id}/read
    public function markAsRead(Request $request, int $id)
    {
        Notification::where('user_id', $request->user()->id)
            ->findOrFail($id)
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'success']);
    }

    // POST /api/notifications/read-all
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'success']);
    }

    // DELETE /api/notifications/{id}
    public function destroy(Request $request, int $id)
    {
        Notification::where('user_id', $request->user()->id)
            ->findOrFail($id)
            ->delete();

        return response()->json(['status' => 'success']);
    }
}