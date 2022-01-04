<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\CreditCard;
use App\Models\PaymentChannel;
use App\Models\StoreCard;
use App\Models\TransactionReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreditCardController extends Controller
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

    public function addCard($user_id, $authorization)
    {
        $find = CreditCard::where('signature', $authorization['signature'])->first();
        if ($find)
        {
            return false;
        }
        return CreditCard::create([
            'user_id'=> $user_id,
            'signature'=>$authorization['signature'],
            'authorization'=>json_encode($authorization)
        ]);
    }

    public function verifyCardAdd(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'reference' => 'required|string|exists:transaction_references, reference',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $secret_key = PaymentChannel::find(1)->secret_key;
        $book = StoreCard::find(TransactionReference::where('reference', $request->reference)
            ->where('type', 'credit_card')->first()->id);

        //The parameter after verify/ is the transaction reference to be verified
        $url = 'https://api.paystack.co/transaction/verify/' . $request->reference;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secret_key]);
        $r = curl_exec($ch);
        curl_close($ch);

        if ($r) {
            $result = json_decode($r, true);

            if ($result) {
                if ($result['data']) {
                    if ($result['data']['status'] == 'success') {

                        $am = $result['data']['amount'];
                        $sam = round($book->amount, 2) * 100;

                        if ($am == $sam && $result['data']['currency'] == $request->currency  && $book->status == '0') {
                            if ($result['data']['authorization']['reusable'])
                            {
                                if($this->addCard($this->user->id, $result['data']['authorization']))
                                {
                                    $m = "Card saved successfully";
                                }
                            }
                            //credit the user 50 Naira from the amount used to verify card
                            (new WalletController())->credit(50);
                            return response([
                                'status'=>true,
                                'message'=>$m??'Card is not reusable',
                                'data'=>[]
                            ]);
                        } else {
                            $message = "Less Amount Paid. Please Contact With Admin";
                        }
                    } else {
                        $message = $result['data']['gateway_response'];
                    }
                } else {
                    $message = $result['message'];
                }
            } else {
                $message = "Something went wrong while executing";
            }
        } else {
            $message = "Something went wrong while executing";
        }

        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => []
        ]);
    }

    public function initiateSaveCard(): \Illuminate\Http\JsonResponse
    {
        $storeCard = StoreCard::create([
            'user_id'=>$this->user->id,
            'amount'=>'50'
        ]);

        $tr = TransactionReference::create([
            'store_card_id'=>$storeCard->id,
            'reference'=>Str::random(),
            'type'=>'credit_card'
        ]);

        return response()->json([
            'status' => true,
            'message' => '',
            'data' => [
                'reference'=>$tr->reference
            ]
        ]);
    }
}
