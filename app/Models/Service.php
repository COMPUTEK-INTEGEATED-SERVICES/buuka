<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'vendor_id'
    ];

    public function resources(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Resource::class, 'resourceable');
    }

    public function vendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id')/*->with(['categories'])*/;
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'service_id', 'id')->with(['resources']);
    }

    public function categories(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(CategoryRelation::class, 'relateable');
    }
}
