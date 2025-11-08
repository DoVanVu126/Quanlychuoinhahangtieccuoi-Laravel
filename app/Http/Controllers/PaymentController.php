<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payment::with('booking');

        // Filter theo booking_id
        if ($request->has('booking_id')) {
            $query->byBooking($request->booking_id);
        }

        // Filter theo payment_status
        if ($request->has('payment_status')) {
            $query->byStatus($request->payment_status);
        }

        // Filter theo payment_method
        if ($request->has('payment_method')) {
            $query->byMethod($request->payment_method);
        }

        // Search theo transaction_code
        if ($request->has('search')) {
            $query->where('transaction_code', 'like', '%' . $request->search . '%');
        }

        // Filter theo khoảng thời gian
        if ($request->has('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $payments = $query->orderBy('payment_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,booking_id',
            'total_amount' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:unpaid,partial,paid',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,e-wallet',
            'transaction_code' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ], [
            'booking_id.required' => 'Vui lòng chọn booking',
            'booking_id.exists' => 'Booking không tồn tại',
            'total_amount.required' => 'Vui lòng nhập tổng tiền',
            'total_amount.min' => 'Tổng tiền phải lớn hơn hoặc bằng 0',
            'payment_status.required' => 'Vui lòng chọn trạng thái thanh toán',
            'payment_status.in' => 'Trạng thái thanh toán không hợp lệ',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Tính remaining_amount
        $depositAmount = $request->deposit_amount ?? 0;
        $remainingAmount = $request->total_amount - $depositAmount;

        $payment = Payment::create([
            'booking_id' => $request->booking_id,
            'total_amount' => $request->total_amount,
            'deposit_amount' => $depositAmount,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $request->payment_status,
            'payment_method' => $request->payment_method,
            'transaction_code' => $request->transaction_code,
            'payment_date' => $request->payment_date ?? now(),
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo thanh toán thành công',
            'data' => $payment
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $payment = Payment::with('booking')->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thanh toán'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thanh toán'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'booking_id' => 'sometimes|required|exists:bookings,booking_id',
            'total_amount' => 'sometimes|required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'sometimes|required|in:unpaid,partial,paid',
            'payment_method' => 'sometimes|required|in:cash,bank_transfer,credit_card,e-wallet',
            'transaction_code' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ], [
            'booking_id.exists' => 'Booking không tồn tại',
            'total_amount.min' => 'Tổng tiền phải lớn hơn hoặc bằng 0',
            'payment_status.in' => 'Trạng thái thanh toán không hợp lệ',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Cập nhật remaining_amount nếu có thay đổi total hoặc deposit
        if ($request->has('total_amount') || $request->has('deposit_amount')) {
            $totalAmount = $request->total_amount ?? $payment->total_amount;
            $depositAmount = $request->deposit_amount ?? $payment->deposit_amount;
            $payment->remaining_amount = $totalAmount - $depositAmount;
        }

        $payment->update($request->except('remaining_amount'));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thanh toán thành công',
            'data' => $payment
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thanh toán'
            ], 404);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa thanh toán thành công'
        ]);
    }

    /**
     * Cập nhật trạng thái thanh toán
     */
    public function updateStatus(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thanh toán'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:unpaid,partial,paid',
        ], [
            'payment_status.required' => 'Vui lòng chọn trạng thái',
            'payment_status.in' => 'Trạng thái không hợp lệ',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->payment_status = $request->payment_status;
        $payment->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $payment
        ]);
    }

    /**
     * Thanh toán tiếp (đóng thêm tiền)
     */
    public function addPayment(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thanh toán'
            ], 404);
        }

        if ($payment->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng đã được thanh toán đầy đủ'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,e-wallet',
            'transaction_code' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ], [
            'amount.required' => 'Vui lòng nhập số tiền thanh toán',
            'amount.min' => 'Số tiền phải lớn hơn 0',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $amount = $request->amount;

        if ($amount > $payment->remaining_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Số tiền thanh toán vượt quá số tiền còn lại'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $payment->deposit_amount += $amount;
            $payment->remaining_amount -= $amount;
            
            if ($payment->remaining_amount <= 0) {
                $payment->payment_status = 'paid';
                $payment->remaining_amount = 0;
            } else {
                $payment->payment_status = 'partial';
            }

            $payment->payment_method = $request->payment_method;
            $payment->transaction_code = $request->transaction_code;
            if ($request->notes) {
                $payment->notes = $request->notes;
            }
            $payment->payment_date = now();
            $payment->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thanh toán thành công',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thống kê thanh toán
     */
    public function statistics(Request $request)
    {
        $query = Payment::query();

        // Filter theo khoảng thời gian
        if ($request->has('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        $stats = [
            'total_payments' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'total_paid' => $query->where('payment_status', 'paid')->sum('total_amount'),
            'total_partial' => $query->where('payment_status', 'partial')->sum('deposit_amount'),
            'total_unpaid' => $query->where('payment_status', 'unpaid')->count(),
            'by_method' => Payment::select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(deposit_amount) as total'))
                ->groupBy('payment_method')
                ->get(),
            'by_status' => Payment::select('payment_status', DB::raw('COUNT(*) as count'))
                ->groupBy('payment_status')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
