<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{

    private $user;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->user = auth()->guard('api')->user();
    }

    public function credit($amount)
    {
        $wallet_balance = Wallet::where('user_id', $this->user->id)->first();
        $wallet_balance->balance = $wallet_balance->balance + $amount;
        return $wallet_balance->save();
    }

    public function debit($amount)
    {
        $wallet_balance = Wallet::where('user_id', $this->user->id)->first();
        $wallet_balance->balance = $wallet_balance->balance - $amount;
        return $wallet_balance->save();
    }
}
