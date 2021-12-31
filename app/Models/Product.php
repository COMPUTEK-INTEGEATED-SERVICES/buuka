<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id','name', 'amount', 'duration'
    ];

    public function service()
    {
        $this->belongsTo(Service::class, 'service_id', 'id');
    }
}
