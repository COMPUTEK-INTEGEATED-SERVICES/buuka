<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified',
        'password',
        'phone',
        'phone_verified',
        'photo',
        'gender',
        'date_of_birth',
        'last_seen'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function wallet(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }

    public function withdrawalRequest(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'user_id', 'id');
    }

    public function staff(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Staff::class, 'user_id', 'id');
    }

    public function routeNotificationForAfricasTalking($notification)
    {
        return $this->phone;
    }

    public function routeNotificationFor($channel)
    {
        if($channel === 'PusherPushNotifications'){
            return "notify-$this->id";
        }

        $class = str_replace('\\', '.', get_class($this));

        return $class.'.'.$this->getKey();
    }
}
