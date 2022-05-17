<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escrow extends Model
{
    use HasFactory;

    protected $fillable = [
        'balance',
        'escrowable_id',
        'escrowable_type',
        'book_id',
        'status'
    ];
}
