<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $primaryKey = 'restaurant_id';

    protected $fillable = [
        'name',
        'description',
        'street',
        'ward',
        'district',
        'city',
        'phone',
        'email',
        'capacity',
        'price_table',
        'star_rating',
        'review_count',
        'image_url',
    ];
}
