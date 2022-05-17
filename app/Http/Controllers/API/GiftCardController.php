<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\GiftCardPurchase;
use App\Models\Resource;
use App\Models\TransactionReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GiftCardController extends Controller
{
    /**
     * @var string[]
     */
    private $allowed_delivery;

    public function __construct()
    {
        $this->allowed_delivery = ['email', 'sms', 'both'];
    }

    public function saveGiftCardInfo(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'gift_card_image_id' => 'required_without:file|int|exists:resources,id',
            'file' => 'nullable|mimes:jpeg,jpg,png,gif,pdf',
            'amount'=>'required|string',
            'delivery'=>'required|string|in'.implode(',', $this->allowed_delivery),
            'to'=>'required|string',
            'from'=>'required|string',
            'message'=>'required|string',
            'quantity'=>'required|int',
            'delivery_date'=>'required|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        for ($x = 0; $x <= $request->quantity; $x++) {
            $code[]=['code'=>Str::upper(Str::random(10)), 'balance'=>$request->amount];
        }
        $g = GiftCardPurchase::create([
            'unit_price'=>$request->amount,
            'quantity'=>$request->quantity,
            'delivery'=>$request->delivery,
            'to'=>$request->to,
            'from'=>$request->from,
            'message'=>$request->message,
            'deliver_date'=>$request->delivery_date
        ]);

        if($request->file){
            //upload file
            $message =  $request->file('file')->store('public/attachments/giftcards');
            Resource::create([
                'path'=>$message,
                'resourceable_id'=>$g->id,
                'resourceable_type'=>'App\Models\GiftCardPurchase'
            ]);
        }

        $transaction = TransactionReference::create([
            'referenceable_id'=>$g->id,
            'reference'=>Str::random(),
            'referenceable_type'=>'App\Models\GiftCardPurchase'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Please proceed to make payment',
            'data' => [
                'reference'=>$transaction->reference
            ]
        ]);
    }

    /** @noinspection SpellCheckingInspection */
    public static function markAsPaid($giftcard)
    {
        for ($x = 1; $x<= $giftcard->quantity; $x++)
        {
            GiftCard::create([
                'purchase_id'=>$giftcard->id,
                'code'=>Str::upper(Str::random(10)),
                'balance'=>$giftcard->unit_price
            ]);
        }
        $giftcard->status = 1;
        return $giftcard->save();
    }

    public function getGiftCardInfo(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'code' => 'required|string|exists:gift_cards,code',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        return response()->json([
            'status' => true,
            'message' => '',
            'data' => [
                'gift_card'=>GiftCard::byCode($request->code)
            ]
        ]);
    }

    public function redeemGiftCard(Request $request): \Illuminate\Http\JsonResponse
    {
        //this route should be in the auth category
        $user = auth()->guard('api')->user();
        $v = Validator::make( $request->all(), [
            'code' => 'required|string|exists:gift_cards,code',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        $giftcard = GiftCard::byCode($request->code);
        if ($giftcard->status = 0)
        {
            WalletController::credit($user->id, 'user', $giftcard->balance);
            $giftcard->balance = 0;
            $giftcard->status = 1;
            $giftcard->save();
            return response()->json([
                'status' => true,
                'message' => 'Gift Card balance has been moved to your wallet',
                'data' => []
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'This Gift Card is empty',
            'data' => []
        ]);
    }
}
