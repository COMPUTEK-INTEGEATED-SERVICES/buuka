<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'walletable_id', 'amount', 'status', 'walletable_type'
    ];

    protected $hidden = [
        'id', 'walletable_id', 'walletable_type', 'created_at', 'updated_at', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
