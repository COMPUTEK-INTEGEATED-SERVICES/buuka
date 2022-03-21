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
        return $this->morphOne(TransactionReference::class, 'referenceable');
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'id', 'vendor_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function products()
    {
        return $this->product_id;
    }
}
