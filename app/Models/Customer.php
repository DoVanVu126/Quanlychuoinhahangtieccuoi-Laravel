<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';
    protected $primaryKey = 'customer_id';
    
    // ✅ CHỈ CÓ created_at, KHÔNG CÓ updated_at (giống User model)
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    public $timestamps = true;
    
    protected $fillable = [
        'user_id',
        'created_at',
    ];

    /**
     * ✅ Relationship với User
     * 1 Customer thuộc về 1 User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * ✅ Relationship với Bookings (nếu có)
     * 1 Customer có nhiều Bookings
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customer_id', 'customer_id');
    }
}