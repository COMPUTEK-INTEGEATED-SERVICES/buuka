<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'path', 'resourceable_id', 'resourceable_type'
    ];

    protected $hidden = [
        'id', 'resourceable_id', 'resourceable_type'
    ];
    public function resourceable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
