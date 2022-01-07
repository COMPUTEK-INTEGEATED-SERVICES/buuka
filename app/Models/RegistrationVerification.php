<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'email_otp', 'sms_otp', 'status'
    ];
}
