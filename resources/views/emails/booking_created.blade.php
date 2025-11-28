<h2>Chúc mừng {{ $user->username }}! Booking của bạn đã được tạo thành công.</h2>
<p>Thông tin booking:</p>
<ul>
    <li>Booking ID: {{ $booking->booking_id }}</li>
    <li>Ngày bắt đầu: {{ \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') }}</li>
    <li>Ngày kết thúc:
        @if($booking->return_date)
            {{ \Carbon\Carbon::parse($booking->return_date)->format('d/m/Y') }}
        @else
            Không có
        @endif
    </li>
    <li>Thời gian: {{ $booking->event_time }}</li>
    <li>Số bàn: {{ $booking->number_of_tables }}</li>
    <li>Tổng tiền: {{ number_format($booking->price, 0, ',', '.') }} VND</li>
</ul>
<p>Cảm ơn bạn đã đặt tiệc!</p>
