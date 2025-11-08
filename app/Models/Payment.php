<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'booking_id',
        'total_amount',
        'deposit_amount',
        'remaining_amount',
        'payment_status',
        'payment_method',
        'transaction_code',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    /**
     * Relationship với Booking
     */
    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class, 'booking_id', 'booking_id');
    }

    /**
     * Kiểm tra đã thanh toán đầy đủ chưa
     */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Kiểm tra đã đặt cọc chưa
     */
    public function hasDeposit()
    {
        return $this->payment_status === 'partial' || $this->payment_status === 'paid';
    }

    /**
     * Scope để lọc theo trạng thái
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope để lọc theo phương thức thanh toán
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope để lọc theo booking
     */
    public function scopeByBooking($query, $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }
}
