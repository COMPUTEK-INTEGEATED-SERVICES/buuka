<?php


namespace App\Http\Controllers\API;


use App\Events\Order\UserBookSuccessfulEvent;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Book;
use App\Models\Client;
use App\Models\Escrow;
use App\Models\Product;
use App\Models\Service;
use App\Models\TransactionReference;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Order\UserBookCompleteNotification;
use App\Notifications\Order\UserBookSuccessfulNotification;
use App\Notifications\Order\UserCanceledOrderNotification;
use App\Notifications\Order\UserMarkedOrderAsCompletedNotification;
use App\Notifications\Order\VendorBookCompleteNotification;
use App\Notifications\Order\VendorCanceledOrderNotification;
use App\Notifications\Order\VendorMarkedOrderAsCompletedNotification;
use App\Notifications\Order\VendorNewBookNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use KingFlamez\Rave\Facades\Rave as Flutterwave;

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
            'scheduled'=>'date_format:Y-m-d H:i|required',
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
            $total_amount = $total_amount + Product::find($p)->price;
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

        $ref = Str::random();
        TransactionReference::create([
            'referenceable_id'=>$book->id,
            'store_card_id'=>0,
            'reference'=>$ref,
            'referenceable_type'=>'App\Models\Book'
        ]);

        $link = (new PaymentController())->initiateFlutterwave($ref);

        if ($link)
        {
            Appointment::create([
                'user_id'=>$this->user->id,
                'vendor_id'=>$request->vendor_id,
                'scheduled'=>Carbon::parse($request->input('scheduled')),
                'book_id'=>$book->id
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
                    'book'=>Book::with('reference')->find($book->id),
                    'link'=>$link
                ]
            ]);
        }
        return response([
            'status'=>false,
            'message'=>'An error has occurred please try again',
            'data'=>[]
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

        Appointment::create([
            'user_id'=>$book->user_id,
            'vendor_id'=>$book->vendor_id,
            'scheduled'=>Carbon::parse($book->scheduled),
            'book_id'=>$book->id
        ]);

        TransactionReference::create([
            'referenceable_id'=>$book->id,
            'store_card_id'=>0,
            'reference'=>Str::random(),
            'referenceable_type'=>'App\Models\Book'
        ]);

        return Book::with('reference')->find($book->id);
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
        if($book->status == 1)
        {
            return true;
        }
        //mark the bok as done
        $book->status = 1;
        //get the vendor and alert them
        try {
            $vendor = Vendor::find($book->vendor_id);
            User::find($book->user_id)->notify(new UserBookCompleteNotification($book, $vendor));
            User::find($vendor->user_id)->notify(new UserBookCompleteNotification($book, $vendor));

            //before returning result, save the vendor's client
            Client::firstOrNew([
                'user_id'=>$book->user_id,
                'vendor_id'=>$book->vendor_id
            ]);

            //move money to escrow account
            EscrowController::addFund($book->vendor_id, 'vendor', $book->amount);
        }catch (\Throwable $throwable)
        {
            report($throwable);
        }

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

    public function markOrderAsCompleted(Request $request): \Illuminate\Http\JsonResponse
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

        $vendor = Vendor::find($book->vendor_id);
        if ($this->user->can('participate', $book, $vendor))
        {
            if ($book->status == 1)
            {
                //notify the vendor that a user marked order as paid or vise versa
                try {
                    User::find($book->user_id)->notify(new VendorMarkedOrderAsCompletedNotification($vendor, $book));
                    User::find($book->vendor_id)->notify(new UserMarkedOrderAsCompletedNotification($book));
                }catch (\Throwable $throwable)
                {
                    report($throwable);
                }

                //change book status to complete
                $book->status = 2;

                //we credit vendor from escrow
                EscrowController::subtractFund($vendor->user_id, 'vendor', $book->amount);
                WalletController::credit($vendor->user_id, 'vendor', $book->amount);

                $book->save();
                return response()->json([
                    'status' => true,
                    'message' => 'This book as been marked as completed',
                    'data' => []
                ]);
            }
        }
        return response()->json([
            'status' => false,
            'message' => 'Access denied',
            'data' => []
        ], 403);
    }

    public function cancelBook(Request $request)
    {
        //apply buuka cancellation policy
        //cancel the order
    }

    public function markOrderAsCanceled(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'book_id' => 'required|int|exists:books,id',
            'reason' => 'nullable|string'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        $book = Book::find($request->book_id);
        $vendor = Vendor::find($book->vendor_id);
        if ($this->user->can('participate', [$book, $vendor])){
            //todo: implement order cancellation policy

            //todo: store cancellation reason
            $book->status = 3;
            $book->save();

            //todo: refund party
            try {
                if ($book->vendor_id == $this->user->id)
                {
                    $this->user->notify(new VendorCanceledOrderNotification($book, $vendor));
                }else{
                    $vendor->user->notify(new UserCanceledOrderNotification($book));
                }
                return response()->json([
                    'status' => true,
                    'message' => 'Order has been cancelled',
                    'data' => [
                        'book'=>$book
                    ]
                ]);
            }catch (\Throwable $throwable)
            {
                report($throwable);
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'Access Denied',
            'data' => []
        ], 403);
    }

    private function associate_flutter_reference($user, $book)
    {
        $data = [
            'payment_options' => 'card,banktransfer',
            'amount' => $book->amount,
            'email' => $user->email,
            'tx_ref' => $book->reference->reference,
            'currency' => "NGN",
            'redirect_url' => route('callback'),
            'customer' => [
                'email' => $user->email,
                "phone_number" => $user->phone??'',
                "name" => $user->first_name.' '.$user->last_name
            ],

            "customizations" => [
                "title" => 'Movie Ticket',
                "description" => "20th October"
            ]
        ];

        return Flutterwave::initializePayment($data);
    }

    public function getBooks(Request $request)
    {
        $allowed_flags = ['PAID', 'UN_PAID', 'COMPLETED', 'CANCELLED'];
        $v = Validator::make($request->all(), [
            'FLAG'=>'nullable|string|in:'.strtoupper(implode(',', $allowed_flags))
        ]);

        if ($v->fails()){
            return response()->json([
                'status'=>false,
                'message'=>'Validation error',
                'data'=>$v->errors()
            ]);
        }

        $books = Book::with(['reference', 'vendor', 'user'])
            ->where('user_id', $this->user->id)
            ->where(function ($query) use ($request) {
                $flag = NULL;
                if (strtoupper($request->FLAG) == 'PAID')
                {
                    $flag = 1;
                }
                if (strtoupper($request->FLAG) == 'UN_PAID')
                {
                    $flag = 0;
                }
                if (strtoupper($request->FLAG) == 'COMPLETED')
                {
                    $flag = 2;
                }
                if (strtoupper($request->FLAG) == 'CANCELLED')
                {
                    //cancelled =3
                    $flag = 3;
                }
                if (!is_null($flag)){
                    $query
                        ->where('status', $flag);
                }
        })->latest()->paginate(10);

        return response()->json([
            'status'=>true,
            'message'=>'',
            'data'=>$books
        ]);
    }
}
