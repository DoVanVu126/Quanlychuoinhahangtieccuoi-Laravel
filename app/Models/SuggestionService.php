<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuggestionService extends Model
{
    use HasFactory;

    protected $table = 'suggestion_services';
    protected $primaryKey = 'suggestion_service_id';
    public $timestamps = true;

    protected $fillable = [
        'package_id',
        'service_id',
    ];

    public function package()
    {
        return $this->belongsTo(SuggestionPackage::class, 'package_id', 'package_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }
}
