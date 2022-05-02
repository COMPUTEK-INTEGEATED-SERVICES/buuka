<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_package_id',
        'user_id',
        'business_name',
        'description',
        'country_id',
        'state_id',
        'city_id',
        'address',
        'week_start',
        'week_end',
        'socials'
    ];

    public function services(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Service::class, 'vendor_id', 'id')->with(['products']);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }

    public function images(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Resource::class, 'resourceable');
    }

    public function staff()
    {
        return $this->hasMany(Staff::class, 'vendor_id', 'id');
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }

    public function categories(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(CategoryRelation::class, 'relateable');
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->with(['user']);
    }

    public static function related_vendors($vendor_id)
    {
        $vendor = Vendor::find($vendor_id);
        return Vendor::where(function ($query) use ($vendor_id, $vendor) {
            $query->where('state_id', '=', $vendor->state_id)
                ->where('city_id', '=', $vendor->city_id)
                ->where('id', '!=', $vendor_id);
        })->inRandomOrder()->take(10)->get();
    }
    public function accounts()
    {
        return $this->morphOne(BankAccount::class, 'account');
    }

    public static function topServiceProvider()
    {
        return self::with(['reviews','images','services', 'staff'])
            ->leftJoin('books', 'books.vendor_id', '=', 'vendors.id')
            ->leftJoin('product_book_relations', 'product_book_relations.book_id', '=', 'books.id')
            ->leftJoin('products', 'product_book_relations.product_id', '=', 'products.id')
            ->where('books.status', 2)
            ->orderBy('products.price')
            ->select('vendors.*')
            ->take(10)->get();
    }

    public function getRatingAttribute()
    {
        $r = Review::vendor_review($this->id);
        //$count =
    }
}
