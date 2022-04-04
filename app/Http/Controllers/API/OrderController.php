<?php


namespace App\Http\Controllers\API;


use App\Events\Order\UserBookSuccessfulEvent;
use App\Http\Controllers\Action\BookActions;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Book;
use App\Models\Client;
use App\Models\Escrow;
use App\Models\Product;
use App\Models\ProductBookRelation;
use App\Models\Service;
use App\Models\TransactionReference;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Order\OrderCancellationNotification;
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

        $book = (new BookActions())->createFixedBook($request, $this->user);

        if ($book)
        {
            return response([
                'status'=>true,
                'message'=>'Product(s) booked proceed to make payment',
                'data'=>$book
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
        return (new BookActions())->createCustomBook($book, $this->user);
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
        return (new BookActions())->markBookAsPaid($book_id);
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
        $book = (new BookActions())->markOrderAsComplete($request->book_id, $this->user);

        if ($book)
        {
            return response()->json([
                'status' => true,
                'message' => 'This book as been marked as completed',
                'data' => []
            ]);
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

            if ($book->status != 3){
                $book->status = 3;
                $book->save();

                //todo: refund party
                try {
                    if ($this->user->id == $book->user_id)
                    {
                        //it is the user
                        $this->user->notify(new OrderCancellationNotification($book));
                        User::find($vendor->user_id)->notify(new UserCanceledOrderNotification($book));
                    }else{
                        $this->user->notify(new OrderCancellationNotification($book));
                        User::find($book->user_id)->notify(new VendorCanceledOrderNotification($book, $vendor));
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
            }else{
                return response()->json([
                    'status' => false,
                    'message' => 'Order is already cancelled',
                    'data' => []
                ], 403);
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

        $books = Book::with(['reference', 'vendor', 'user', 'products'])
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
