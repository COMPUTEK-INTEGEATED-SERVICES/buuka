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
        return self::with(['appointment', 'products'])
            ->where('status', 0)
            ->where('vendor_id', $vendor_id)
            ->latest()->take(10)->get();
    }

    public static function inProgress($vendor_id)
    {
        return self::with(['appointment', 'products'])
            ->where('status', 1)
            ->where('vendor_id', $vendor_id)
            ->latest()->take(10)->get();
    }

    public static function totalSales($vendor_id)
    {
        return self::with(['appointment', 'products'])
            ->where('status', 2)
            ->where('vendor_id', $vendor_id)
            ->latest()->take(10)->get();
    }

    public static function totalBookings($vendor_id, $user)
    {
        return self::where('status', 1)
            ->where('user_id', '!=', $user->id)
            ->orWhere('status', 2)
            ->where('vendor_id', $vendor_id)
            ->count();
    }

    public static function activeBookings($vendor_id, $user)
    {
        return self::where('status', 1)
            ->where('user_id', '!=', $user->id)
            ->where('vendor_id', $vendor_id)
            ->count();
    }

    public static function pendingSalesAmount($vendor_id, $user)
    {
        return self::where('vendor_id', $vendor_id)
            ->where('user_id', '!=', $user->id)
            ->where('status', 1)
            ->leftJoin('product_book_relations', 'product_book_relations.book_id', '=', 'books.id')
            ->leftJoin('products', 'product_book_relations.product_id', '=', 'products.id')
            ->sum('products.price');
    }

    public static function totalSalesAmount($vendor_id, $user)
    {
        return self::where('vendor_id', $vendor_id)
            ->where('user_id', '!=', $user->id)
            ->where('status', 1)
            ->orWhere('status', 2)
            ->leftJoin('product_book_relations', 'product_book_relations.book_id', '=', 'books.id')
            ->leftJoin('products', 'product_book_relations.product_id', '=', 'products.id')
            ->sum('products.price');
    }
}
