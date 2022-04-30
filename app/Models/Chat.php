<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'message', 'type', 'user_id', 'vendor_id', 'from', 'staff_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $appends = ['book'];

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function vendor(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Vendor::class, 'id', 'vendor_id');
    }

    public static function senderReceiver($sender, $receiver)
    {
        return self::where(function ($query) use ($sender, $receiver){
            $query->where('user_1', $sender)
                ->where('user_2', $receiver);
        })->where(function ($query) use ($sender, $receiver){
            $query->where('user_1', $receiver)
                ->where('user_2', $sender);
        })->get();
    }

    public function getBookAttribute()
    {

        return $this->type === 'book'?Book::with('products')->find($this->message):null;

    }
}
