<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description'
    ];

    public function images()
    {
        return $this->hasMany(ServiceImages::class, 'service_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'service_id', 'id');
    }
}
