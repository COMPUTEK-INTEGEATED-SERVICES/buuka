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

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public static function vendor_review($vendor_id)
    {
        return self::where('reviewable_type', 'App\Models\Vendor')
            ->where('reviewable_id', $vendor_id);
    }
}
