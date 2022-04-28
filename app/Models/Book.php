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
        return $this->hasMany(ProductBookRelation::class, 'book_id', 'id')->with('product');
    }

    public function appointment()
    {
        return $this->hasOne(Appointment::class, 'book_id', 'id');
    }

    public static function pendingSales($vendor_id)
    {
        return self::with(['appointment', 'products'])->where('status', 0)->latest()->take(10)->get();
    }

    public static function inProgress($vendor_id)
    {
        return self::with(['appointment', 'products'])->where('status', 1)->latest()->take(10)->get();
    }

    public static function totalSales($vendor_id)
    {
        return self::with(['appointment', 'products'])->where('status', 2)->latest()->take(10)->get();
    }
}
