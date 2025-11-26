<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuggestionFood extends Model
{
    use HasFactory;

    protected $table = 'suggestion_foods';
    protected $primaryKey = 'suggestion_food_id';
    public $timestamps = true;

    protected $fillable = [
        'package_id',
        'food_id',
    ];

    public function package()
    {
        return $this->belongsTo(SuggestionPackage::class, 'package_id', 'package_id');
    }

    public function food()
    {
        return $this->belongsTo(Food::class, 'food_id', 'food_id');
    }
}
