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
}
