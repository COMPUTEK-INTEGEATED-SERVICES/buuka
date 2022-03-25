<?php


namespace App\Http\Controllers\API;


use App\Events\Chat\NewChatMessage;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'message' => 'required_without:file|string',
            'user_id' => 'required|integer|exists:users,id',
            'vendor_id' => 'required|integer|exists:vendors,id',
            'from' => 'required|string|in:'.strtoupper(implode(',',$allowed_from)),
            'file' => 'nullable|mimes:jpeg,jpg,png,gif,pdf',
            'book'=>'nullable|array',
            'book.product'=>'integer|exists:products,id',
            'book.amount'=>'string',
            'book.scheduled'=>'string',
            'book.extras'=>'string',
            'book.vendor_id'=>'int',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->canSendMessage($request->user_id, $request->vendor_id, strtoupper($request->from)))
        {
            if($request->file){
                //upload file
                $message =  $request->file('file')->store('public/attachments');

                if (in_array($request->file->extension(), $allowed_image))
                {
                    $type = 'image';
                }else{
                    $type = 'document';
                }
                //$type = $request->file('file')->getMimeType();
            }else{

                $message = $request->message;
            }

            if ($request->book)
            {
                $message = (new OrderController())->customBook($request->input('book'))->id;
                //type is book to show that a book was made here
                $type = 'book';
            }

            $vendor = Vendor::find($request->vendor_id);
            $chat = Chat::create([
                'vendor_id'=>$request->input('vendor_id'),
                'user_id'=>$request->input('user_id'),
                'type'=>$type??'text',
                'message'=>$message,
                'from'=>strtoupper($request->from),
                'staff_id'=> $vendor->user_id
            ]);

            try {
                broadcast( new NewChatMessage($chat, $this->user, $vendor));
            }catch (\Throwable $throwable){
                report($throwable);
            }

            return response([
                'status'=>true,
                'message'=>'Chat sent',
                'data'=>[
                ]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Not authorized',
            'data'=>[]
        ], 401);
    }

    private function canSendMessage($user_id, $vendor_id, $from)
    {
        if ($from == 'USER')
        {
            return $this->user->id == $user_id;
        }else{
            $vendor = Vendor::find($vendor_id);
            return $this->user->id == $vendor->user_id;
        }
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
            })->latest()->paginate(10);

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
                    $query->where('staff_id', $this->user->id);
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
                $query->where('staff_id', $this->user->id);
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
