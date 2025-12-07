<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    // 1. Tên bảng trong database
    protected $table = 'support_tickets';

    // 2. CỰC KỲ QUAN TRỌNG: Định nghĩa khóa chính
    // Vì trong migration bạn để $table->id('ticket_id'), nên ở đây bắt buộc phải khai báo
    protected $primaryKey = 'ticket_id';

    // 3. Khai báo các cột được phép thêm dữ liệu (Mass Assignment)
    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subject',
        'message',
        'priority', // default: low
        'status',   // default: new
    ];

    // 4. (Tùy chọn) Thiết lập quan hệ với bảng User
    public function user()
    {
        // belongsTo(Model liên kết, khóa ngoại bảng này, khóa chính bảng kia)
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    
    // 5. (Tùy chọn) Thiết lập quan hệ với bảng Notifications
    // Một ticket có thể có nhiều thông báo liên quan
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'support_ticket_id', 'ticket_id');
    }
}