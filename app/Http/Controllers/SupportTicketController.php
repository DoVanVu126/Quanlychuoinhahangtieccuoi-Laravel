<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\Notification; // ✅ Quan trọng: Phải import Model Notification
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    // 1. [ADMIN] Lấy danh sách ticket (có lọc & phân trang)
    public function index(Request $request)
    {
        $query = SupportTicket::query();

        // Tìm kiếm
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Lọc trạng thái
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Lọc ưu tiên
        if ($request->has('priority') && $request->priority != '') {
            $query->where('priority', $request->priority);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 10);
        return response()->json($query->paginate($perPage));
    }

    // 2. [CUSTOMER] Gửi yêu cầu hỗ trợ mới
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $ticket = SupportTicket::create([
                'user_id' => $user->user_id,
                'customer_name' => $user->full_name ?? $user->username,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone,
                'subject' => $request->subject,
                'message' => $request->message,
                'priority' => $request->priority,
                'status' => 'new',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gửi yêu cầu thành công',
                'data' => $ticket
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server: ' . $e->getMessage()], 500);
        }
    }

    // 3. [ADMIN] Cập nhật trạng thái ticket
    public function update(Request $request, $id)
    {
        $ticket = SupportTicket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Không tìm thấy yêu cầu'], 404);
        }

        $ticket->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $ticket
        ]);
    }

    // 4. [ADMIN] Trả lời và Tạo thông báo (Hàm hoàn chỉnh)
    public function reply(Request $request, $id) 
    {
        // Validate nội dung trả lời
        $request->validate([
            'reply_message' => 'required|string'
        ]);

        $ticket = SupportTicket::find($id);
        
        if (!$ticket) {
            return response()->json(['message' => 'Ticket không tồn tại'], 404);
        }

        // 1. Cập nhật trạng thái Ticket thành "Đã giải quyết" (hoặc đang xử lý)
        $ticket->status = 'resolved'; 
        $ticket->save();

        // 2. Tạo Thông báo cho Khách hàng
        Notification::create([
            'user_id' => $ticket->user_id, // Gửi về cho chủ ticket
            'title' => 'Phản hồi yêu cầu #' . $ticket->ticket_id,
            'message' => 'Admin đã trả lời: ' . $request->reply_message,
            'type' => 'support_reply',
            'support_ticket_id' => $ticket->ticket_id, // Link tới ticket gốc
            'is_read' => false
        ]);

        // (Tùy chọn) Gửi Email cho khách hàng ở đây nếu cần
        // Mail::to($ticket->customer_email)->send(new SupportReplyMail($ticket, $request->reply_message));

        return response()->json([
            'success' => true, 
            'message' => 'Đã gửi phản hồi và thông báo cho khách hàng'
        ]);
    }
}