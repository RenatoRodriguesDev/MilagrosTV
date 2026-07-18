<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = UserNotification::where('user_id', Auth::id())
            ->latest()
            ->limit(20)
            ->get();
        return response()->json($notifications);
    }

    public function markRead()
    {
        UserNotification::where('user_id', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);
        return response()->json(['ok' => true]);
    }

    public function unreadCount()
    {
        $count = UserNotification::where('user_id', Auth::id())
            ->where('read', false)
            ->count();
        return response()->json(['count' => $count]);
    }

    public function destroy(UserNotification $notification)
    {
        if ($notification->user_id !== Auth::id()) abort(403);
        $notification->delete();
        return response()->json(['ok' => true]);
    }
}
