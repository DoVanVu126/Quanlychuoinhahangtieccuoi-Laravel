<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\Events\NewNotificationEvent;

class NotificationController extends Controller
{
    /**
     * Gửi thông báo (realtime + lưu DB)
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Lưu DB
        $notif = Notification::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'message' => $request->message,
        ]);

        // Gửi realtime event
        broadcast(new NewNotificationEvent($notif))->toOthers();

        return response()->json([
            'message' => 'Đã gửi thông báo',
            'notification' => $notif
        ]);
    }


    /**
     * Lấy danh sách thông báo của user
     */
    public function getNotifications($userId)
    {
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }


    /**
     * Đánh dấu 1 thông báo đã đọc
     */
    public function markRead($id)
    {
        Notification::where('id', $id)->update(['is_read' => true]);

        return response()->json(['message' => 'Đã đánh dấu đã đọc']);
    }


    /**
     * (Bonus) Đánh dấu tất cả thông báo user đã đọc
     */
    public function markAllRead($userId)
    {
         Notification::where('user_id', $userId)->update(['is_read' => true]);
    return response()->json(['message' => 'Đã đánh dấu tất cả đã đọc']);
    }

    // Xóa 1 thông báo
public function deleteNotif($id) {
    Notification::where('id', $id)->delete();
    return response()->json(['message' => 'Đã xóa thông báo']);
}

// Xóa tất cả thông báo của user
public function deleteAll($userId) {
    Notification::where('user_id', $userId)->delete();
    return response()->json(['message' => 'Đã xóa tất cả thông báo']);
}
public function sendToast(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer',
        'title' => 'required|string|max:255',
        'message' => 'required|string',
        'type' => 'nullable|string', // success, info, warning, error
    ]);

    $notif = Notification::create([
        'user_id' => $request->user_id,
        'title' => $request->title,
        'message' => $request->message,
        'type' => $request->type ?? 'info',
    ]);

    // Broadcast realtime event (Echo / Pusher / Laravel Websockets)
    broadcast(new NewNotificationEvent($notif))->toOthers();

    return response()->json([
        'message' => 'Đã gửi thông báo Toast',
        'notification' => $notif
    ]);
}
}
