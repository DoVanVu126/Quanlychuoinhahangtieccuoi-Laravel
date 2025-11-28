<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Events\NewNotificationEvent;

class NotificationController extends Controller
{
    // Lấy danh sách thông báo theo user
    public function index($user_id)
    {
        return Notification::where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Tạo thông báo mới
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required',
            'title' => 'required|string',
            'message' => 'required|string',
            'type' => 'nullable|string',
            'booking_id' => 'nullable|integer',
            'promotion_id' => 'nullable|integer',
        ]);

        $notification = Notification::create($data);

        // Gửi realtime
        event(new NewNotificationEvent($notification));

        return response()->json(['success' => true, 'notification' => $notification]);
    }

    // Đánh dấu 1 thông báo đã đọc
    public function markRead($id)
    {
        $n = Notification::findOrFail($id);
        $n->is_read = 1;
        $n->save();

        return response()->json(['success' => true]);
    }

    // Đánh dấu tất cả đã đọc
    public function markAllRead($user_id)
    {
        Notification::where('user_id', $user_id)->update(['is_read' => 1]);

        return response()->json(['success' => true]);
    }

    // Xóa 1 thông báo
    public function delete($id)
    {
        Notification::where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    // Xóa tất cả thông báo
    public function deleteAll($user_id)
    {
        Notification::where('user_id', $user_id)->delete();

        return response()->json(['success' => true]);
    }
}
