<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id', 'user_id', 'comment', 'star', 'reviewable_id', 'reviewable_type'
    ];
}
