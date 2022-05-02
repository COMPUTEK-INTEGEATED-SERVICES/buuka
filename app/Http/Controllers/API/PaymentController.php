<?php


namespace App\Http\Controllers\API;


use App\Events\PaymentEvent;
use App\Http\Controllers\Action\BookActions;
use App\Http\Controllers\Action\PaymentAction;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Book;
use App\Models\CreditCard;
use App\Models\GiftCard;
use App\Models\GiftCardPurchase;
use App\Models\PaymentChannel;
use App\Models\PaymentMethod;
use App\Models\TransactionHistory;
use App\Models\TransactionReference;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Notifications\WithdrawalSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\False_;
use function Composer\Autoload\includeFile;
use KingFlamez\Rave\Facades\Rave as Flutterwave;

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
            'reference' => 'required|string|exists:transaction_references,reference',
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
            ->where('referenceable_type', 'App\Models\Book')->first()->id);

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
                            (new BookActions())->markBookAsPaid($book->id);
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

    public function verifyGiftCardPurchase(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'reference' => 'required|string|exists:transaction_references, reference',
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
        $gcard = GiftCardPurchase::find(TransactionReference::where('reference', $request->reference)
            ->where('referenceable_type', 'App\Models\GiftCardPurchase')->first()->id);

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
                        $amount = $gcard->quantity * $gcard->unit_price;
                        $sam = round($amount, 2) * 100;

                        if ($am == $sam && $result['data']['currency'] == $request->currency  && $gcard->status == 0) {
                            $gcard->status = 1;
                            $gcard->save();
                            for ($x = 0; $x <= $gcard->quantity; $x++)
                            {
                                GiftCard::create([
                                    'purchase_id'=>$gcard->id,
                                    'code'=>Str::upper(Str::random(12)),
                                    'balance'=>$gcard->unit_price
                                ]);
                            }
                            return response([
                                'status'=>true,
                                'message'=>'Order purchased confirmed',
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
            'reference' => 'required|string|exists:transaction_references,reference',
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
            ->where('referenceable_type', 'App\Models\Book')->first()->id);

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
                            (new BookActions())->markBookAsPaid($book->id);
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

    public function processPaymentWithGiftCard(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'reference' => 'required|string|exists:transaction_references, reference',
            'code' => 'required|string|exists:gift_cards,code',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $book = Book::find(TransactionReference::where('reference', $request->reference)
            ->where('referenceable_type', 'App\Models\Book')->first()->id);
        $giftcard = GiftCard::find($request->code);
        if ($giftcard->status == 1)
        {
            return response()->json([
                'status' => false,
                'message' => "This Gift Card is empty",
                'data' => []
            ]);
        }
        switch ($giftcard->balance)
        {
            case $book->amount == $giftcard->balance:
                //debit the giftcard
                GiftCard::debit($giftcard->id, $book->amount);
                $giftcard->status = 1;
                $giftcard->save();
                (new BookActions())->markBookAsPaid($book->id);
                break;
            case $book->amount < $giftcard->balance:
                //debit the giftcard
                GiftCard::debit($giftcard->id, $book->amount);
                (new BookActions())->markBookAsPaid($book->id);
                break;
            case $book->amount > $giftcard->balance:
                //we subtract the balance from the book amount
                //before which we must have set the actual amount to book amount
                //save the book amount and send a response
                $actual_amount = $book->amount;
                $book->amount = $book->amount - $giftcard->balance;
                $book->actual_amount = $actual_amount;
                //debit the giftcard
                $giftcard->balance = 0;
                $giftcard->status = 1;
                $giftcard->save();
                $book->save();
                return response()->json([
                    'status' => true,
                    'message' => "GiftCard has been applied to the order",
                    'data' => []
                ]);
        }
        return response()->json([
            'status' => false,
            'message' => "An error has occurred",
            'data' => []
        ], 422);
    }

    public function initiateFlutterwaveForWallet(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'amount' => 'required|int',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        $user = auth()->guard()->user();

        $reference = TransactionReference::create([
            'referenceable_id'=>$user->wallet->id,
            'store_card_id'=>0,
            'reference'=>Str::random(),
            'referenceable_type'=>'App\Models\Wallet'
        ]);

        $data = [
            'payment_options' => 'card,banktransfer',
            'amount' => $request->amount,
            'email' => $user->email,
            'tx_ref' => $reference->reference,
            'currency' => "NGN",
            'redirect_url' => route('callback'),
            'customer' => [
                'email' => $user->email,
                "phone_number" => $user->phone??'',
                "name" => $user->first_name.' '.$user->last_name
            ],

            "customizations" => [
                "title" => 'Wallet Top-up',
                "description" => ""
            ]
        ];

        $payment = PaymentAction::initiateFlutter($data);

        return response()->json([
            'status' => true,
            'message' => 'Link generated',
            'data' => [
                'link'=>$payment,
                'reference'=>$reference->reference
            ]
        ]);
    }

    public function initiateFlutterwaveForBook($reference)
    {
        $book_id = TransactionReference::where('reference', $reference)
            ->where('referenceable_type', 'App\Models\Book')->first()->referenceable_id;
        $user = auth()->guard()->user();
        $book = Book::find($book_id);

        if ($book)
        {
            $data = [
                'payment_options' => 'card,banktransfer',
                'amount' => $book->amount,
                'email' => $user->email,
                'tx_ref' => $reference,
                'currency' => "NGN",
                'redirect_url' => route('callback'),
                'customer' => [
                    'email' => $user->email,
                    "phone_number" => $user->phone??'',
                    "name" => $user->first_name.' '.$user->last_name
                ],

                "customizations" => [
                    "title" => 'Order Book',
                    "description" => ""
                ]
            ];

            $payment = PaymentAction::initiateFlutter($data);
        }

        return $payment??false;
    }

    public function flutterwaveConfirmPayment(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'tx_ref' => 'required|string|exists:transaction_references,reference',
            'status'=>'required|string',
            'transaction_id'=>'required|string'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $transactionID = Flutterwave::getTransactionIDFromCallback();
        $data = Flutterwave::verifyTransaction($transactionID);
        $d = (object)$data;

        if ($d->status != 'error'){
            $data = (object)$d->data;
            //if payment is successful
            if ($data->status ==  'successful') {
                //get what the payment is for
                //Log::error(json_encode($data));
                $ref = TransactionReference::where('reference', $data->tx_ref)->first();
                switch ($ref->referenceable_type){
                    case "App\Models\Book":
                        $book = Book::find($ref->referenceable_id);

                        if($data->amount == $book->amount && $data->currency == 'NGN')
                        {
                            try {
                                (new BookActions())->markBookAsPaid($book->id);
                                return response([
                                    'status'=>true,
                                    'message'=>'Payment received with thanks',
                                    'data'=>[
                                        'book'=>$book,
                                    ]
                                ]);
                            }catch (\Throwable $throwable){
                                report($throwable);
                                return response([
                                    'status'=>false,
                                    'message'=>'An error occurred please retry confirmation',
                                    'data'=>[
                                        'book'=>$book,
                                    ]
                                ]);
                            }
                        }
                        break;
                    case "App\Models\Wallet":
                        $wallet = Wallet::find($ref->referenceable_id);
                        if($data->amount && $data->currency == 'NGN')
                        {
                            try {
                                WalletController::credit($wallet->walletable_id, 'user', $data->amount);
                                return response([
                                    'status'=>true,
                                    'message'=>'Wallet has been funded with '. $data->amount,
                                    'data'=>[
                                        'wallet'=>$wallet,
                                    ]
                                ]);
                            }catch (\Throwable $throwable){
                                report($throwable);
                                return response([
                                    'status'=>false,
                                    'message'=>'An error occurred please retry confirmation',
                                    'data'=>[
                                        'wallet'=>$wallet,
                                    ]
                                ]);
                            }
                        }
                        break;
                }
            }
            if ($data->status ==  'cancelled'){
                return response([
                    'status'=>false,
                    'message'=>'You cancelled this transaction',
                ]);
            }
        }
        return response([
            'status'=>false,
            'message'=>'An error occurred please try again',
        ]);
    }

    public function flutterwaveWebhook(Request $request)
    {
        //This verifies the webhook is sent from Flutterwave
        $verified = Flutterwave::verifyWebhook();

        // if it is a charge event, verify and confirm it is a successful transaction
        if ($verified && $request->event == 'charge.completed' && $request->data->status == 'successful') {
            $verificationData = Flutterwave::verifyPayment($request->data['id']);
            if ($verificationData['status'] === 'success') {
                // process for successful charge

                //send payment received event
                broadcast(new PaymentEvent($request->data->tx_ref, $status = 'success'));

                $book = Book::find(TransactionReference::where('reference', $request->data->tx_ref)
                    ->where('referenceable_type', 'App\Models\Book')->first()->referenceable_id);
                (new BookActions())->markBookAsPaid($book->id);
            }
            broadcast(new PaymentEvent($request->data->tx_ref, $verificationData['status']));
        }

        // if it is a transfer event, verify and confirm it is a successful transfer
        if ($verified && $request->event == 'transfer.completed') {

            $transfer = Flutterwave::transfers()->fetch($request->data['id']);

            if($transfer['data']['status'] === 'SUCCESSFUL') {
                // update transfer status to successful in your db
            } else if ($transfer['data']['status'] === 'FAILED') {
                // update transfer status to failed in your db
                // revert customer balance back
            } else if ($transfer['data']['status'] === 'PENDING') {
                // update transfer status to pending in your db
            }

        }

    }

    public function processPaymentWithWalletBalance(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'reference' => 'required|string|exists:transaction_references,reference',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        try {
            $book_id = TransactionReference::where('reference', $request->reference)
                ->where('referenceable_type', 'App\Models\Book')->first()->referenceable_id;
            $book = Book::find($book_id);
            $user = User::find($book->user_id);

            if ($user->wallet->balance < $book->amount){
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient funds in your wallet',
                    'data' => []
                ]);
            }

            if ($book->status == 2){
                return response()->json([
                    'status'=>true,
                    'message'=>'Order Booked Already',
                    'data'=>[]
                ]);
            }

            //debit the user
            WalletController::debit($user->id, 'user', $book->amount);

            //mark order as paid
            $paid = (new BookActions())->markBookAsPaid($book->id);
            if ($paid){
                return response()->json([
                    'status'=>true,
                    'message'=>'Order Booked Successfully',
                    'data'=>[]
                ]);
            }
        }catch (\Throwable $throwable){
            report($throwable);
        }

        return response()->json([
            'status'=>false,
            'message'=>'An error occurred please try again',
        ]);
    }

    public function resolveBankAccountFlutter(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'account_number' => 'required|string',
            'account_bank' => 'required|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        try {
            $url = "https://api.flutterwave.com/v3/accounts/resolve";
            $field = [
                'account_number'=>$request->account_number,
                'account_bank'=>$request->account_bank
            ];
            $response = Http::withToken(env('FLW_SECRET_KEY'))
                ->asForm()->post($url, $field);
            //return response(gettype($response->json()));
            if ($response->ok() && $response->json()['status'] =='success'){
                return response()->json([
                    'status' => true,
                    'message' => 'Account details',
                    'data' => $response->json()['data']
                ]);
            }
        }catch (\Throwable $throwable){
            report($throwable);
        }
        return response()->json([
            'status' => false,
            'message' => $response->json()['message'],
            'data' => []
        ]);
    }

    public function withdrawVendor(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->guard('auth:api')->user();
        $vendor = Vendor::where('user_id', $user->id)->first();

        $v = Validator::make( $request->all(), [
            'account_number' => 'required|string',
            'bank_id' => 'required|int|exists:banks,id',
            'amount' => 'required|string|max:'.$user->wallet->balance,
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        try {
            if ($request->amount <= $user->wallet->balance){
                //firstornew account details
                $account = BankAccount::firstOrNew(
                    ['account_id'=>$vendor->id],
                    ['account_type'=>"App\Models\Vendor"],
                    ['account_number'=>$request->account_number],
                    ['bank_id'=>$request->bank_id]
                );
                $bank = Bank::find($account->bank_id);
                $ref = TransactionReference::create([
                    'referenceable_id'=>$vendor->id,
                    'reference'=>Str::random(),
                    'referenceable_type'=>"App\Models\Vendor"
                ]);

                $history = TransactionHistory::create([
                    'history_id'=>$vendor->id,
                    'history_type'=>"App\Models\Vendor",
                    'amount'=>$request->amount,
                    'note'=>'vendor withdrew money'
                ]);

                //withdraw from vendor balance
                WalletController::debit($vendor->wallet->id, 'vendor', $request->amount);

                $endpoint = "https://api.flutterwave.com/v3/transfers";
                $field = [
                    "account_bank" => $bank->code,
                    "account_number" => $account->account_number,
                    "amount" => $request->amount,
                    "narration" => "Buuka Withdrawal",
                    "currency" => "NGN",
                    "reference" => $ref->reference,
                    "callback_url" => url('callback'),
                    "debit_currency" => "NGN"
                ];
                $response = Http::withToken(env('FLW_SECRET_KEY'))->post($endpoint, $field);
                if($response->ok()){
                    $response = $response->json();
                    if($response['status'] == "success"){
                        $history -> status = 1;
                        $history->save();

                        //notify the vendor
                        $user->notify(new WithdrawalSubmittedNotification($vendor));
                        return response()->json([
                            'status'=>true,
                            'message'=>'Withdrawal request submitted',
                            'data'=>[]
                        ]);
                    }
                }else{
                    $history -> status = 1;
                    $history->save();

                    //notify the vendor
                    $user->notify(new WithdrawalSubmittedNotification($vendor));
                    return response()->json([
                        'status'=>false,
                        'message'=>'An error occurred please contact support',
                        'data'=>[]
                    ]);
                }
            }
        }catch (\Throwable $throwable){
            report($throwable);
        }
        return response()->json([
            'status'=>false,
            'message'=>'An error occurred please contact support',
            'data'=>[]
        ], 500);
    }
}
