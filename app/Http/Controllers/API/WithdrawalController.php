<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WithdrawalController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */

    private $user;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->user = auth()->guard('api')->user();
    }

    public function storeWithdrawalRequest(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'user_id' => 'required|integer|exists:banks,id',
            'amount'=>'required|string',
            'account_name'=>'string|required',
            'account_number'=>'string|required',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $wallet = $this->user->wallet;

        if($wallet->balance < $request->input('amount')){
            return response()->json([
                'status' => false,
                'message' => 'Insufficient Fund',
                'data' => []
            ]);
        }

        (new WalletController())->debit($request->input('amount'));

        WithdrawalRequest::create([
            'user_id'=>$request->input('name'),
            'amount'=>$request->input('description'),
            'account_name'=>$request->input('account_name'),
            'account_number'=>$request->input('account_number')
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Withdrawal Request Submitted',
            'data' => []
        ]);


    }
}
