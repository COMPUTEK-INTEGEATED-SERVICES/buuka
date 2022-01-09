<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorPackage extends Model
{
    use HasFactory;

    protected $fillable =[
        'name',
        'tax',
        'commission',
        'color'
    ];
}
