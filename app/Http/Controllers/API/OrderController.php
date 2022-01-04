<?php


namespace App\Http\Controllers\API;


use App\Events\Order\UserBookSuccessfulEvent;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Client;
use App\Models\Product;
use App\Models\Service;
use App\Models\TransactionReference;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Order\UserBookCompleteNotification;
use App\Notifications\Order\UserBookSuccessfulNotification;
use App\Notifications\Order\VendorBookCompleteNotification;
use App\Notifications\Order\VendorNewBookNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
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

    public function fixedBook(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'vendor_id' => 'required|int|exists:vendors,id',
            'product_id' => 'required|array',
            'product_id.*' => 'exists:products,id',
            'note'=>'nullable|string',
            'scheduled'=>'string|required',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        /**
         * here i will take the product_id and book it against amount and other details
         */
        //here i will want to get the total amount
        $total_amount = 0;
        foreach ($request->input('product_id') as $p)
        {
            $total_amount = $total_amount + Product::find($p)->amount;
        }

        $book = Book::create([
            'user_id'=>$this->user->id,
            'vendor_id'=>$request->vendor_id,
            'product_id'=>json_encode($request->input('product_id')),
            'schedule'=>Carbon::make($request->input('scheduled')),
            'amount'=>$total_amount,
            'note'=>$request->input('note'),
            'type'=>'fixed'
        ]);

        TransactionReference::create([
            'book_id'=>$book->id,
            'reference'=>Str::random(),
            'type'=>'book'
        ]);

        try {
            $this->user->notify(new UserBookSuccessfulNotification($book));
            broadcast( new UserBookSuccessfulEvent($book, $this->user));
        }catch (\Throwable $throwable){
            report($throwable);
        }

        return response([
            'status'=>true,
            'message'=>'Product(s) booked proceed to make payment',
            'data'=>[
                'book'=>$book,
            ]
        ]);
    }

    public function customBook($book)
    {
        $book =  Book::create([
            'user_id'=>$this->user->id,
            'vendor_id'=>$book->vendor_id,
            'product_id'=>json_encode([$book->product_id]),
            'schedule'=>$book->scheduled,
            'amount'=>$book->amount,
            'note'=>$book->extras,
            'type'=>'custom',
            'proposed_by'=>($book->vendor_id == $book->user_id)?'vendor':'client'
        ]);

        TransactionReference::create([
            'book_id'=>$book->id,
            'reference'=>Str::random()
        ]);

        return $book;
    }

    public function getSingleOrder(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'book_id' => 'required|int|exists:books,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'book'=>Book::with('reference')->find($request->book_id),
            ]
        ]);
    }

    public function completeOrder($book_id)
    {
        $book = Book::find($book_id);
        //mark the bok as done
        $book->status = 1;
        //get the vendor and alert them
        try {
            $vendor = Vendor::find($book->vendor_id);
            Notification::send(User::find($book->user_id), new UserBookCompleteNotification($book, $vendor));
            Notification::send(User::find($book->vendor_id), new VendorBookCompleteNotification($book, $vendor));
        }catch (\Throwable $throwable)
        {
            report($throwable);
        }
        //before returning result, save the vendor's client
        Client::firstOrNew([
            'user_id'=>$book->user_id,
            'vendor_id'=>$book->vendor_id
        ]);
        //finally save the book
        return $book->save();
    }

    public function acceptOrderProposal(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'book_id' => 'required|int|exists:books,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        $book = Book::find($request->book_id);
        if ($book->type === 'custom')
        {
            if (($book->proposed_by == 'vendor' && $this->user->id != $book->vendor_id) || ($book->proposed_by == 'user' && $this->user->id != $book->user_id))
            {
                $book->custom_book_accepted = 1;
                $book->save();
                TransactionReference::create([
                    'book_id'=>$book->id,
                    'reference'=>Str::random(),
                    'type'=>'book'
                ]);

                try {
                    $user = User::find($book->user_id);
                    $user->notify(new UserBookSuccessfulNotification($book));
                    broadcast( new UserBookSuccessfulEvent($book, $user));
                }catch (\Throwable $throwable){
                    report($throwable);
                }

                return response([
                    'status'=>true,
                    'message'=>'Product(s) booked proceed to make payment',
                    'data'=>[
                        'book'=>$book,
                    ]
                ]);
            }
            return response([
                'status'=>false,
                'message'=>'Invalid permission',
                'data'=>[]
            ], 403);
        }
        return response([
            'status'=>false,
            'message'=>'Invalid request',
            'data'=>[]
        ], 422);
    }
}
