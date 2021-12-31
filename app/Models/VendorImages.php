<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorImages extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'image', 'type'
    ];
}
