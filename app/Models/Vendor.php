<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'description',
        'country_id',
        'state_id',
        'city_id',
        'address',
        'week_start',
        'week_end',
        'socials'
    ];

    public function services()
    {
        $this->hasMany(Service::class, 'vendor_id', 'id');
    }
}
