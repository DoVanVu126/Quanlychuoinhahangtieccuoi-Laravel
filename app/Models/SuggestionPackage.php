<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuggestionPackage extends Model
{
    use HasFactory;

    protected $table = 'suggestion_packages';
    protected $primaryKey = 'package_id';
    public $timestamps = false;

    protected $fillable = [
        'restaurant_id',
        'name',
        'event_type',
        'hall_id',
        'number_of_tables',
        'description',
        'image_url',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'restaurant_id');
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class, 'hall_id', 'hall_id');
    }

    public function foods()
    {
        return $this->belongsToMany(Food::class, 'suggestion_foods', 'package_id', 'food_id')->withTimestamps();
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'suggestion_services', 'package_id', 'service_id')->withTimestamps();
    }
}
