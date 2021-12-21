<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description'
    ];

    public function images()
    {
        return $this->hasMany(ServiceImages::class, 'service_id', 'id');
    }

    public function prices()
    {
        return $this->hasMany(ServicePrices::class, 'service_id', 'id');
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'category_id', 'id');
    }
}
