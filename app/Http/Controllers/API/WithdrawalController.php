<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\PaymentChannel;
use App\Models\Vendor;
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

    public function storeWithdrawalRequest(Request $request): \Illuminate\Http\JsonResponse
    {
        //allowed types incase a user calls to withdraw in future
        $allowed_types = ['vendor', 'user'];
        $v = Validator::make( $request->all(), [
            'bank_id' => 'required|integer|exists:banks,id',
            'amount'=>'required|string',
            'account_name'=>'string|required',
            'account_number'=>'string|required',
            'type'=>'nullable|string|in:'.strtolower(implode(',', $allowed_types))
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $vendor = Vendor::with('wallet')->where('user_id', $this->user->id)->first();
        if ($request->type == 'user'){
            $wallet = $this->user->wallet;
        }else{
            $wallet = $vendor->wallet;
        }

        if($wallet->balance < $request->input('amount')){
            return response()->json([
                'status' => false,
                'message' => 'Insufficient Fund',
                'data' => []
            ],422);
        }

        $checkrequest = WithdrawalRequest::where('user_id', $this->user->id)
            ->where('status',0)->first();
        if($checkrequest) {
            return response()->json([
                'status' => false,
                'message' => 'You have a pending withdrawal request',
                'data' => []
            ],422);
        }

        (new WalletController())->debit(($request->type == 'user')?$this->user->id:$vendor->id, ($request->type == 'user')?'user':'vendor', $request->input('amount'));

        WithdrawalRequest::create([
            'user_id'=>$this->user->id,
            'bank_id'=>$request->input('bank_id'),
            'amount'=>$request->input('amount'),
            'account_name'=>$request->input('account_name'),
            'account_number'=>$request->input('account_number')
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Withdrawal Request Submitted',
            'data' => []
        ]);


    }

    public function getUserBankName(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'bank_id' => 'required|integer|exists:banks,id',
            'account_number'=>'string|required',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        $bank = Bank::find($request->bank_id);
        $secret_key = PaymentChannel::find(1)->secret_key;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/bank/resolve?account_number={$request->input('account_number')}&bank_code=$bank->code",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer $secret_key",
                "Cache-Control: no-cache",
            ),
        ));
        $response = json_decode(curl_exec($curl));
        $err = curl_error($curl);
        curl_close($curl);
        if (!$err) {
            return response()->json([
                'status' => true,
                'message' => $response->message,
                'data' => $response->data
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => $err??'Invalid Account number supplied',
            'data' => []
        ]);
    }
}
