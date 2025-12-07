<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Events\NewNotificationEvent;
use Illuminate\Support\Facades\Auth; // Thêm Facade Auth

class NotificationController extends Controller
{
    // Lấy danh sách thông báo (Tự động lấy theo User đang đăng nhập)
    public function index()
    {
        // ✅ SỬA: Lấy ID từ Auth thay vì tham số URL
        $userId = Auth::id(); 

        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(20) // Giới hạn 20 thông báo mới nhất để nhẹ API
            ->get();
    }

    // Tạo thông báo mới (Cập nhật thêm support_ticket_id)
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required',
            'title' => 'required|string',
            'message' => 'required|string',
            'type' => 'nullable|string',
            'booking_id' => 'nullable|integer',
            'promotion_id' => 'nullable|integer',
            'support_ticket_id' => 'nullable|integer', // ✅ THÊM DÒNG NÀY
        ]);

        $notification = Notification::create($data);

        // Gửi realtime
        event(new NewNotificationEvent($notification));

        return response()->json(['success' => true, 'notification' => $notification]);
    }

    // Đánh dấu 1 thông báo đã đọc
    public function markRead($id)
    {
        // ✅ SỬA: Chỉ cho phép đánh dấu thông báo của chính mình
        $n = Notification::where('user_id', Auth::id())
                         ->where('id', $id)
                         ->firstOrFail();
                         
        $n->is_read = 1;
        $n->save();

        return response()->json(['success' => true]);
    }

    // Đánh dấu tất cả đã đọc
    public function markAllRead()
    {
        $userId = Auth::id(); // ✅ SỬA: Lấy từ Auth
        Notification::where('user_id', $userId)->update(['is_read' => 1]);

        return response()->json(['success' => true]);
    }

    // Xóa 1 thông báo
    public function delete($id)
    {
        // ✅ SỬA: Chỉ xóa thông báo của chính mình
        Notification::where('user_id', Auth::id())
                    ->where('id', $id)
                    ->delete();

        return response()->json(['success' => true]);
    }

    // Xóa tất cả thông báo
    public function deleteAll()
    {
        $userId = Auth::id(); // ✅ SỬA: Lấy từ Auth
        Notification::where('user_id', $userId)->delete();

        return response()->json(['success' => true]);
    }
}