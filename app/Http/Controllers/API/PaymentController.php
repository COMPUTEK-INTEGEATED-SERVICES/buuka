<?php


namespace App\Http\Controllers\API;


use App\Models\Book;
use App\Models\CreditCard;
use App\Models\PaymentChannel;
use App\Models\PaymentMethod;
use App\Models\TransactionReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function Composer\Autoload\includeFile;

class PaymentController extends \App\Http\Controllers\Controller
{
    public function payment_settings(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => '',
            'data' => [
                'channels'=>PaymentChannel::all()
            ]
        ]);
    }

    public function payment_methods(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => '',
            'data' => [
                'methods'=>PaymentMethod::all()
            ]
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'reference' => 'required|string|exists:transaction_references, reference',
            'save_card'=>'nullable|bool',
            'currency'=>'required|string|exists:countries, currency'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $secret_key = PaymentChannel::find(1)->secret_key;
        $book = Book::find(TransactionReference::where('reference', $request->reference)
            ->where('type', 'book')->first()->id);

        $result = [];
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
                            if ($request->save_card)
                            {
                                $m = 'Order booked, card is not reusable';
                                if($result['data']['authorization']['reusable'])
                                {
                                    //the user wants card to be saved
                                    $save = (new CreditCardController())->addCard($book->user_id, $result['data']['authorization']);

                                    ($save)?$m = 'Order booked, card saved':$m = 'Order booked, card not saved';
                                }
                            }
                            (new OrderController)->completeOrder($book);
                            return response([
                                'status'=>true,
                                'message'=>$m??'Order book completed',
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

    public function processPaymentWithSavedCard(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'reference' => 'required|string|exists:transaction_references, reference',
            'card_id'=>'required|int|exists:credit_cards, id',
            'currency'=>'required|string|exists:countries, currency'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $user = auth()->guard()->user();
        $secret_key = PaymentChannel::find(1)->secret_key;
        $book = Book::find(TransactionReference::where('reference', $request->reference)
            ->where('type', 'book')->first()->id);

        $card = CreditCard::find($request->card_id);
        if ($card->user_id != $user->id)
        {
            return response([
                'status'=>false,
                'message'=>'Access denied',
                'data'=>[]
            ], 403);
        }
        $authorization = json_decode($card->authorization)['authorization_code'];
        $result = [];
        //The parameter after verify/ is the transaction reference to be verified
        $url = "https://api.paystack.co/transaction/charge_authorization";
        $fields = [
            'authorization_code' => $authorization,
            'email' => $user->email,
            'amount' => $book->amount,
            'reference'=>$request->reference
        ];

        $fields_string = http_build_query($fields);
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $secret_key",
            "Cache-Control: no-cache",
        ));
        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
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
                            (new OrderController)->completeOrder($book);
                            return response([
                                'status'=>true,
                                'message'=>'Order book completed',
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
}
