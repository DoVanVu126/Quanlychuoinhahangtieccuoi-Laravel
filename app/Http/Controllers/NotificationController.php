<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\Events\NewNotificationEvent;

class NotificationController extends Controller
{
    /**
     * Gửi thông báo cho user (lưu DB + realtime)
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|in:success,info,warning,error',
            'booking_id' => 'nullable|integer',
            'promotion_id' => 'nullable|integer',
            'membership_id' => 'nullable|integer',
        ]);

        $notif = Notification::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type ?? 'info',
            'booking_id' => $request->booking_id,
            'promotion_id' => $request->promotion_id,
            'membership_id' => $request->membership_id,
        ]);

        // Gửi realtime event (Pusher / Laravel Echo / WebSockets)
        broadcast(new NewNotificationEvent($notif))->toOthers();

        return response()->json([
            'message' => 'Đã gửi thông báo',
            'notification' => $notif
        ]);
    }

    /**
     * Lấy danh sách tất cả thông báo của user
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
        $notif = Notification::findOrFail($id);
        $notif->update(['is_read' => true]);

        return response()->json(['message' => 'Đã đánh dấu đã đọc']);
    }

    /**
     * Đánh dấu tất cả thông báo của user đã đọc
     */
    public function markAllRead($userId)
    {
        Notification::where('user_id', $userId)->update(['is_read' => true]);

        return response()->json(['message' => 'Đã đánh dấu tất cả đã đọc']);
    }

    /**
     * Xóa 1 thông báo
     */
    public function deleteNotif($id)
    {
        $notif = Notification::findOrFail($id);
        $notif->delete();

        return response()->json(['message' => 'Đã xóa thông báo']);
    }

    /**
     * Xóa tất cả thông báo của user
     */
    public function deleteAll($userId)
    {
        Notification::where('user_id', $userId)->delete();

        return response()->json(['message' => 'Đã xóa tất cả thông báo']);
    }

    /**
     * Gửi thông báo dạng toast (lưu DB + realtime)
     */
    public function sendToast(Request $request)
    {
        // Reuse sendNotification để tránh lặp code
        return $this->sendNotification($request);
    }
}
