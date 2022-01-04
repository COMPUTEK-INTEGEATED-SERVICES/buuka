<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'product_id',
        'schedule',
        'amount',
        'note',
        'type',
        'payment_method_id',
        'custom_book_accepted',
        'proposed_by',
        'status',
    ];

    public function reference()
    {
        $this->hasOne(TransactionReference::class, 'book_id', 'id');
    }
}
