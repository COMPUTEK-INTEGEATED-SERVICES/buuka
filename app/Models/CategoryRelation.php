<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryRelation extends Model
{
    use HasFactory;

    protected $fillable = [
        'relateable_id', 'relateable_type', 'category_id'
    ];
}
