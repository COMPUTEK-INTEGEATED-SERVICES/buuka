<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStaffRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_relation_id', 'product_relation_type', 'staff_id'
    ];
}
