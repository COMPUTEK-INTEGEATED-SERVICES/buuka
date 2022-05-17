<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Escrow;
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

    public static function credit($id, $type, $amount)
    {
        if ($type === 'user') {
            $type = 'App\Models\User';
        } else {
            $type = 'App\Models\Vendor';
        }

        $wallet = Wallet::where('walletable_id', $id)
            ->where('walletable_type', $type)->first();
        $wallet->balance = $wallet->balance + $amount;
        return $wallet->save();
    }

    public static function debit($id, $type, $amount)
    {
        if ($type === 'user') {
            $type = 'App\Models\User';
        } else {
            $type = 'App\Models\Vendor';
        }

        $wallet = Wallet::where('walletable_id', $id)
            ->where('walletable_type', $type)->first();
        $wallet->balance = $wallet->balance - $amount;
        return $wallet->save();
    }
}
