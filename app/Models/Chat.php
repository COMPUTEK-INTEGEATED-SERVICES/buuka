<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'message', 'type', 'user_1', 'user_2'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function sender(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_1');
    }

    public function receiver(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_2');
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
}
