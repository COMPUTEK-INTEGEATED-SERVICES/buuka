<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Escrow;
use App\Models\User;
use Illuminate\Http\Request;

class EscrowController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->user = auth()->guard('api')->user();
    }

    public static function addFund($id, $type, $amount)
    {
        if ($type == 'user') {
            $type = 'App\Models\User';
        } else {
            $type = 'App\Models\Vendor';
        }

        $escrow = Escrow::where('escrowable_id', $id)
            ->where('escrowable_type', $type)->first();
        $escrow->balance = $escrow->balance + $amount;
        return $escrow->save();
    }

    public static function subtractFund($id, $type, $amount)
    {
        if ($type == 'user') {
            $type = 'App\Models\User';
        } else {
            $type = 'App\Models\Vendor';
        }

        $escrow = Escrow::where('escrowable_id', $id)
            ->where('escrowable_type', $type)->first();
        $escrow->balance = $escrow->balance - $amount;
        return $escrow->save();
    }
}
