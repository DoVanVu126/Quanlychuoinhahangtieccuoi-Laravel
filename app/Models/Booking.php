<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'bookings';
    protected $primaryKey = 'booking_id';
    public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'created_by_user_id',
        'restaurant_id',
        'hall_id',
        'event_type',
        'event_time',
        'event_date',
        'return_date',
        'number_of_tables',
        'status',
        'notes',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'customer_id' => 'integer',
        'created_by_user_id' => 'integer',
        'restaurant_id' => 'integer',
        'hall_id' => 'integer',
        'event_time' => 'string', // hoặc datetime:H:i:s
        'event_date' => 'date',
        'return_date' => 'date',
        'number_of_tables' => 'integer',
    ];

    public function hall()
    {
        return $this->belongsTo(Hall::class, 'hall_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function foods()
    {
        return $this->belongsToMany(Food::class, 'booking_foods', 'booking_id', 'food_id')->withTimestamps();
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'booking_services', 'booking_id', 'service_id')->withTimestamps();
    }
}
