<?php


namespace App\Http\Controllers\API;


use App\Events\Chat\NewChatMessage;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Chat;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
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

    public function sendMessage(Request $request)
    {
        $allowed_image = ['jpeg', 'png', 'gif', 'jpg'];
        $allowed_from = ['USER', 'VENDOR'];
        $v = Validator::make( $request->all(), [
            'message' => 'nullable|string',
            'user_id' => 'required|integer|exists:users,id',
            'vendor_id' => 'required|integer|exists:vendors,id',
            'from' => 'required|string|in:'.strtoupper(implode(',',$allowed_from)),
            'file' => 'nullable|mimes:jpeg,jpg,png,gif,pdf',
            'book'=>'nullable|array',
            'book.product'=>'required_with:book|integer|exists:products,id',
            'book.amount'=>'required_with:book|string',
            'book.scheduled'=>'required_with:book|date_format:Y-m-d H:i',
            'book.extras'=>'required_with:book|string',
            'book.id'=>'nullable|int|exists:books,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        //vendor
        $vendor = Vendor::find($request->vendor_id);
        //user
        $user = User::find($request->user_id);

        if ($this->user->can('can_send_message', [User::class, $user, $vendor, $request]))
        {
            try {
                if($request->file){
                    //upload file
                    $message =  $request->file('file')->store('public/attachments');

                    if (in_array($request->file->extension(), $allowed_image))
                    {
                        $type = 'image';
                    }else{
                        $type = 'document';
                    }
                }elseif($request->message){

                    $message = $request->message;
                }
                else {
                    $book = (new OrderController())->customBook($request->book, $this->user, $vendor);
                    $message = $book->id;
                    //type is book to show that a book was made here
                    $type = 'book';
                }

                $chat = Chat::create([
                    'vendor_id'=>$vendor->id,
                    'user_id'=>$user->id,
                    'type'=>$type??'text',
                    'message'=>$message,
                    'from'=>strtoupper($request->from),
                    'staff_id'=> $vendor->user_id
                ]);

                if ($this->user->id === $vendor->user_id){
                    //the user is notified
                    $user->notify(new NewMessageNotification(User::find($vendor->user_id), $chat, $vendor));
                }else{
                    User::find($vendor->user_id)->notify(new NewMessageNotification($user, $chat));
                }
                return response([
                    'status'=>true,
                    'message'=>'Chat sent',
                    'data'=>[
                        'book'=>$book??null
                    ]
                ]);
            }catch (\Throwable $throwable){
                report($throwable);
                return response([
                    'status'=>true,
                    'message'=>'Sorry an error occurred',
                    'data'=>[]
                ],422);
            }
        }

        return response([
            'status'=>false,
            'message'=>'Not authorized',
            'data'=>[]
        ], 401);
    }

    private function canInteract():bool
    {
        return true;
    }

    public function getMessages(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'vendor_id' => 'required|integer|exists:vendors,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        if($this->canInteract())
        {
            $chat = Chat::with(['user', 'vendor'])->where(function ($query) use ($request) {
                $query->where('vendor_id', $request->input('vendor_id'))
                    ->where('user_id', $request->input('user_id'));
            })->latest()->get();

            if ($chat)
            {
                return response([
                    'status'=>true,
                    'message'=>'',
                    'data'=>[
                        'chat'=>$chat
                    ]
                ]);
            }
            return response([
                'status'=>false,
                'message'=>'No messages',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Not authorized',
            'data'=>[]
        ], 401);
    }

    public function getStarredMessages(Request $request)
    {
        $allowed_as = ['USER', 'VENDOR'];
        $v = Validator::make( $request->all(), [
            'as' => 'required|string|in:'.strtoupper(implode(',', $allowed_as)),
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        $chat = Chat::with(['user', 'vendor'])->where(function ($query) use ($request) {
            if ($request->input('as') == 'USER'){
                $query->where('user_id', $this->user->id);
            }else{
                $query->where('staff_id', $this->user->id);
            }
        })->where('starred', 1)
            ->latest()->paginate(10);

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'chat'=>$chat
            ]
        ]);
    }

    public function getAllMessages(Request $request)
    {
        $allowed_as = ['USER', 'VENDOR'];
        $v = Validator::make( $request->all(), [
            'as' => 'required|string|in:'.strtoupper(implode(',', $allowed_as)),
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        $chat = Chat::with(['user', 'vendor'])
            ->where(function($query) use ($request) {
                if ($request->input('as') == 'USER'){
                    $query->where('user_id', $this->user->id);
                }else{
                    $query->where('vendor_id', Vendor::where('user_id', $this->user->id)->first()->id);
                }
            })
            ->latest()->paginate(10);

        $key_by = (strtoupper($request->as) == 'USER')? 'vendor_id': 'user_id';
        $chatCollection = $chat->getCollection()->keyBy($key_by);
        $chat->setCollection($chatCollection);

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'chat'=>$chat
            ]
        ]);
    }

    public function getDeletedMessages(Request $request)
    {
        $allowed_as = ['USER', 'VENDOR'];
        $v = Validator::make( $request->all(), [
            'as' => 'required|string|in:'.strtoupper(implode(',', $allowed_as)),
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        $chat = Chat::with(['user', 'vendor'])->where(function ($query) use ($request) {
            if ($request->input('as') == 'USER'){
                $query->where('user_id', $this->user->id);
            }else{
                $query->where('vendor_id', Vendor::where('user_id', $this->user->id)->first());
            }
        })->where('deleted', 1)
            ->latest()->paginate(10);

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'chat'=>$chat
            ]
        ]);
    }

    public function getNewMessages(Request $request)
    {
        $allowed_as = ['USER', 'VENDOR'];
        $v = Validator::make( $request->all(), [
            'as' => 'required|string|in:'.strtoupper(implode(',', $allowed_as)),
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        $chat = Chat::with(['sender', 'receiver'])->where(function ($query) use ($request) {
            if ($request->input('as') == 'USER'){
                $query->where('user_id', $this->user->id);
            }else{
                $query->where('staff_id', $this->user->id);
            }
        })->where('read', 1)
            ->latest()->paginate(10);

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'chat'=>$chat
            ]
        ]);
    }
}
